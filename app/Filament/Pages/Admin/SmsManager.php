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
                        'concierges' => "Concierges ({$counts['concierges']})",
                        'pending_concierges' => "Pending Concierges ({$counts['pending_concierges']})",
                        'partners' => "Partners ({$counts['partners']})",
                        'venues' => "Venues ({$counts['venues']})",
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

        $conciergesQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        if (filled($selectedRegions)) {
            $conciergesQuery->where(function ($query) use ($selectedRegions) {
                foreach ($selectedRegions as $region) {
                    $query->orWhereJsonContains('notification_regions', $region);
                }
            });
        }

        $pendingConciergesQuery = Referral::query()
            ->where('type', 'concierge')
            ->whereNull('secured_at')
            ->whereNotNull('phone');

        if (filled($selectedRegions)) {
            $pendingConciergesQuery->where(function ($query) use ($selectedRegions) {
                $query->whereIn('region_id', $selectedRegions)
                    ->orWhereNull('region_id');
            });
        }

        $partnersQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'partner'))
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        if (filled($selectedRegions)) {
            $partnersQuery->where(function ($query) use ($selectedRegions) {
                foreach ($selectedRegions as $region) {
                    $query->orWhereJsonContains('notification_regions', $region);
                }
            });
        }

        $venuesQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'venue'))
            ->whereNotNull('phone');

        if (filled($selectedRegions)) {
            $venuesQuery->where(function ($query) use ($selectedRegions) {
                foreach ($selectedRegions as $region) {
                    $query->orWhereJsonContains('notification_regions', $region);
                }
            });
        }

        return [
            'concierges' => $conciergesQuery->count(),
            'pending_concierges' => $pendingConciergesQuery->count(),
            'partners' => $partnersQuery->count(),
            'venues' => $venuesQuery->count(),
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

    public function getSelectedRecipients(): Collection
    {
        $data = $this->data;
        $recipients = collect($data['recipients'] ?? []);
        $query = null;

        if ($recipients->contains('concierges')) {
            $query = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
                ->whereNotNull('secured_at')
                ->whereNotNull('phone')
                ->select('first_name', 'last_name', 'phone')
                ->selectRaw("'concierge' as role_type");

            if (filled($data['regions'])) {
                $query->where(function ($query) use ($data) {
                    foreach ($data['regions'] as $region) {
                        $query->orWhereJsonContains('notification_regions', $region);
                    }
                });
            }
        }

        if ($recipients->contains('pending_concierges')) {
            $pendingQuery = Referral::query()
                ->where('type', 'concierge')
                ->whereNull('secured_at')
                ->whereNotNull('phone')
                ->select('first_name', 'last_name', 'phone')
                ->selectRaw("'pending_concierge' as role_type");

            if (filled($data['regions'])) {
                $pendingQuery->where(function ($query) use ($data) {
                    $query->whereIn('region_id', $data['regions'])
                        ->orWhereNull('region_id');
                });
            }

            $query = $query ? $query->union($pendingQuery) : $pendingQuery;
        }

        if ($recipients->contains('partners')) {
            $partnersQuery = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'partner'))
                ->whereNotNull('secured_at')
                ->whereNotNull('phone')
                ->select('first_name', 'last_name', 'phone')
                ->selectRaw("'partner' as role_type");

            if (filled($data['regions'])) {
                $partnersQuery->where(function ($query) use ($data) {
                    foreach ($data['regions'] as $region) {
                        $query->orWhereJsonContains('notification_regions', $region);
                    }
                });
            }

            $query = $query ? $query->union($partnersQuery) : $partnersQuery;
        }

        if ($recipients->contains('venues')) {
            $venuesQuery = User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'venue'))
                ->whereNotNull('phone')
                ->select('first_name', 'last_name', 'phone')
                ->selectRaw("'venue' as role_type");

            if (filled($data['regions'])) {
                $venuesQuery->where(function ($query) use ($data) {
                    foreach ($data['regions'] as $region) {
                        $query->orWhereJsonContains('notification_regions', $region);
                    }
                });
            }

            $query = $query ? $query->union($venuesQuery) : $venuesQuery;
        }

        return $query ? $query->get() : User::query()->whereRaw('1=0')->get();
    }
}
