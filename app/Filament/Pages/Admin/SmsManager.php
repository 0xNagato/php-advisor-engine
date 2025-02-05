<?php

namespace App\Filament\Pages\Admin;

use App\Jobs\SendBulkSmsJob;
use App\Models\Referral;
use App\Models\Region;
use App\Models\User;
use Exception;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class SmsManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $title = 'SMS Manager';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.admin.sms-manager';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $counts = $this->getRecipientCounts();

        return $form->schema([
            Grid::make(2)->schema([
                CheckboxList::make('data.recipients')
                    ->hiddenLabel()
                    ->options([
                        'concierges_with_bookings' => new HtmlString("<div class='flex flex-col'><div class='text-[10px] text-gray-500'>CONCIERGE</div><div>Has Bookings ({$counts['concierges_with_bookings']})</div></div>"),
                        'concierges_active_no_bookings' => new HtmlString("<div class='flex flex-col'><div class='text-[10px] text-gray-500'>CONCIERGE</div><div>No Bookings ({$counts['concierges_active_no_bookings']})</div></div>"),
                        'concierges_inactive' => new HtmlString("<div class='flex flex-col'><div class='text-[10px] text-gray-500'>CONCIERGE</div><div>Inactive ({$counts['concierges_inactive']})</div></div>"),
                        'pending_concierges' => new HtmlString("<div class='flex flex-col'><div class='text-[10px] text-gray-500'>CONCIERGE</div><div>Pending ({$counts['pending_concierges']})</div></div>"),
                        'partners' => new HtmlString("Partners ({$counts['partners']})"),
                        'venues' => new HtmlString("Venues ({$counts['venues']})"),
                    ])
                    ->columns(2)
                    ->gridDirection('row')
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                CheckboxList::make('data.regions')
                    ->label('Target Regions')
                    ->helperText('Only send to users in selected regions. Leave empty to send to all regions.')
                    ->options(Region::query()->orderBy('name')->pluck('name', 'id'))
                    ->gridDirection('row')
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->form->fill([
                            'data' => [
                                'recipients' => $this->data['recipients'] ?? [],
                                'regions' => $this->data['regions'] ?? [],
                                'message' => $this->data['message'] ?? '',
                            ],
                        ]);
                    })
                    ->columnSpanFull(),

                Textarea::make('data.message')
                    ->hiddenLabel()
                    ->placeholder('Type your message here...')
                    ->required()
                    ->maxLength(1600)
                    ->rows(6)
                    ->extraInputAttributes(['class' => 'text-sm'])
                    ->columnSpanFull(),

                Placeholder::make('message_info')
                    ->hiddenLabel()
                    ->content(view('filament.pages.admin.partials.sms-info', [
                        'recipientCounts' => $counts,
                    ]))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function getRecipientCounts(): array
    {
        $selectedRegions = $this->data['regions'] ?? [];
        $sevenDaysAgo = now()->subDays(7);

        // Concierges with completed bookings
        $conciergesWithBookingsQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
            ->whereHas('concierge.bookings', function ($query) {
                $query->whereNotNull('confirmed_at');
            })
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        // Active concierges without bookings
        $conciergesActiveNoBookingsQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
            ->whereDoesntHave('concierge.bookings', function ($query) {
                $query->whereNotNull('confirmed_at');
            })
            ->whereHas('authentications', function ($query) use ($sevenDaysAgo) {
                $query->where('login_at', '>=', $sevenDaysAgo);
            })
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        // Inactive concierges
        $conciergesInactiveQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
            ->whereDoesntHave('authentications', function ($query) use ($sevenDaysAgo) {
                $query->where('login_at', '>=', $sevenDaysAgo);
            })
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        // Apply region filters if selected
        if (filled($selectedRegions)) {
            $regionFilter = function ($query) use ($selectedRegions) {
                $query->where(function ($subQuery) use ($selectedRegions) {
                    foreach ($selectedRegions as $region) {
                        $subQuery->orWhereJsonContains('notification_regions', $region);
                    }
                });
            };

            $conciergesWithBookingsQuery->where($regionFilter);
            $conciergesActiveNoBookingsQuery->where($regionFilter);
            $conciergesInactiveQuery->where($regionFilter);
        }

        // Update the pending concierges count logic
        $pendingConciergesQuery = Referral::query()
            ->where('type', 'concierge')
            ->whereNull('secured_at')
            ->whereNotNull('phone');

        // Only apply region filter if regions are selected
        if (filled($selectedRegions)) {
            $pendingConciergesQuery->whereIn('region_id', $selectedRegions);
        }

        return [
            'concierges_with_bookings' => $conciergesWithBookingsQuery->count(),
            'concierges_active_no_bookings' => $conciergesActiveNoBookingsQuery->count(),
            'concierges_inactive' => $conciergesInactiveQuery->count(),
            'pending_concierges' => $pendingConciergesQuery->count(),
            'partners' => $this->getPartnersQuery($selectedRegions)->count(),
            'venues' => $this->getVenuesQuery($selectedRegions)->count(),
        ];
    }

    public function send(): void
    {
        try {
            $message = $this->data['message'];
            $recipients = $this->getSelectedRecipients();

            // Log total count and recipient details
            logger()->info('SMS Recipients Count: '.$recipients->count());
            logger()->info('SMS Recipients:', $recipients->map(fn ($recipient) => [
                'name' => $recipient->first_name.' '.$recipient->last_name,
                'phone' => $recipient->phone,
                'role' => $recipient->role_type,
            ])->toArray());

            $phoneNumbers = $recipients->pluck('phone')->filter()->unique();

            $phoneNumbers->chunk(50)->each(function ($chunk) use ($message) {
                dispatch(new SendBulkSmsJob($chunk->toArray(), $message));
            });

            Notification::make()
                ->title('Success')
                ->body('SMS messages have been queued for sending')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to queue SMS messages: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function applyRegionFilter(Builder $query, array $regions): void
    {
        if (filled($regions)) {
            $query->where(function ($subQuery) use ($regions) {
                foreach ($regions as $region) {
                    $subQuery->orWhereJsonContains('notification_regions', $region);
                }
            });
        }
    }

    public function getSelectedRecipients(): Collection
    {
        $data = $this->data;
        $recipients = collect($data['recipients'] ?? []);
        $query = null;
        $sevenDaysAgo = now()->subDays(7);

        if ($recipients->contains('concierges_with_bookings')) {
            $query = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
                ->whereHas('concierge.bookings', function ($query) {
                    $query->whereNotNull('confirmed_at');
                })
                ->whereNotNull('secured_at')
                ->whereNotNull('phone');

            $this->applyRegionFilter($query, $data['regions'] ?? []);

            $query->select('first_name', 'last_name', 'phone')
                ->selectRaw("'concierge_with_bookings' as role_type");
        }

        if ($recipients->contains('concierges_active_no_bookings')) {
            $activeNoBookingsQuery = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
                ->whereDoesntHave('concierge.bookings', function ($query) {
                    $query->whereNotNull('confirmed_at');
                })
                ->whereHas('authentications', function ($query) use ($sevenDaysAgo) {
                    $query->where('login_at', '>=', $sevenDaysAgo);
                })
                ->whereNotNull('secured_at')
                ->whereNotNull('phone');

            $this->applyRegionFilter($activeNoBookingsQuery, $data['regions'] ?? []);

            $activeNoBookingsQuery->select('first_name', 'last_name', 'phone')
                ->selectRaw("'concierge_active_no_bookings' as role_type");

            $query = $query ? $query->union($activeNoBookingsQuery) : $activeNoBookingsQuery;
        }

        if ($recipients->contains('concierges_inactive')) {
            $inactiveQuery = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
                ->whereDoesntHave('authentications', function ($query) use ($sevenDaysAgo) {
                    $query->where('login_at', '>=', $sevenDaysAgo);
                })
                ->whereNotNull('secured_at')
                ->whereNotNull('phone');

            $this->applyRegionFilter($inactiveQuery, $data['regions'] ?? []);

            $inactiveQuery->select('first_name', 'last_name', 'phone')
                ->selectRaw("'concierge_inactive' as role_type");

            $query = $query ? $query->union($inactiveQuery) : $inactiveQuery;
        }

        if ($recipients->contains('pending_concierges')) {
            $pendingQuery = Referral::query()
                ->where('type', 'concierge')
                ->whereNull('secured_at')
                ->whereNotNull('phone')
                ->whereNotNull('region_id')
                ->select('first_name', 'last_name', 'phone')
                ->selectRaw("'pending_concierge' as role_type");

            if (filled($data['regions'])) {
                $pendingQuery->whereIn('region_id', $data['regions']);
            }

            $query = $query ? $query->union($pendingQuery) : $pendingQuery;
        }

        if ($recipients->contains('partners')) {
            $partnersQuery = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'partner'))
                ->whereNotNull('secured_at')
                ->whereNotNull('phone');

            $this->applyRegionFilter($partnersQuery, $data['regions'] ?? []);

            $partnersQuery->select('first_name', 'last_name', 'phone')
                ->selectRaw("'partner' as role_type");

            $query = $query ? $query->union($partnersQuery) : $partnersQuery;
        }

        if ($recipients->contains('venues')) {
            $venuesQuery = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'venue'))
                ->whereNotNull('phone');

            $this->applyRegionFilter($venuesQuery, $data['regions'] ?? []);

            $venuesQuery->select('first_name', 'last_name', 'phone')
                ->selectRaw("'venue' as role_type");

            $query = $query ? $query->union($venuesQuery) : $venuesQuery;
        }

        if ($query) {
            $results = $query->get();

            return $results;
        }

        return User::query()->whereRaw('1=0')->get();
    }

    private function getPendingConciergesQuery(array $selectedRegions): Builder
    {
        $query = Referral::query()
            ->where('type', 'concierge')
            ->whereNull('secured_at')
            ->whereNotNull('phone')
            ->whereNotNull('region_id');

        if (filled($selectedRegions)) {
            $query->whereIn('region_id', $selectedRegions);
        }

        return $query;
    }

    private function getPartnersQuery(array $selectedRegions): Builder
    {
        $query = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'partner'))
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        if (filled($selectedRegions)) {
            $query->where(function ($query) use ($selectedRegions) {
                foreach ($selectedRegions as $region) {
                    $query->orWhereJsonContains('notification_regions', $region);
                }
            });
        }

        return $query;
    }

    private function getVenuesQuery(array $selectedRegions): Builder
    {
        $query = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'venue'))
            ->whereNotNull('phone')
            ->select('first_name', 'last_name', 'phone')
            ->selectRaw("'venue' as role_type");

        if (filled($selectedRegions)) {
            $query->where(function ($query) use ($selectedRegions) {
                foreach ($selectedRegions as $region) {
                    $query->orWhereJsonContains('notification_regions', $region);
                }
            });
        }

        return $query;
    }
}
