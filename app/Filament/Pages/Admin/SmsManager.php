<?php

namespace App\Filament\Pages\Admin;

use App\Jobs\ProcessScheduledSmsJob;
use App\Jobs\SendBulkSmsJob;
use App\Models\Referral;
use App\Models\Region;
use App\Models\ScheduledSms;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class SmsManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $title = 'SMS Manager';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.admin.sms-manager';

    public ?array $data = [];

    public bool $isScheduling = false;

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
                Toggle::make('isScheduling')
                    ->label('Schedule for later')
                    ->live()
                    ->afterStateUpdated(function (bool $state) {
                        if (! $state) {
                            // Reset scheduling data if toggled off
                            $this->data['scheduled_time'] = null;
                        } elseif (blank($this->data['scheduled_time'])) {
                            // Set default to 10 minutes in the future
                            $userTimezone = auth()->user()->timezone ?? config('app.timezone');
                            $this->data['scheduled_time'] = now()->timezone($userTimezone)->addMinutes(10)->format('Y-m-d H:i:s');
                        }
                    })
                    ->columnSpanFull(),

                DateTimePicker::make('data.scheduled_time')
                    ->label('Scheduled Time (Your Local Time)')
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('M j, Y g:i A')
                    // Use user's local timezone for input
                    ->timezone(auth()->user()->timezone)
                    ->statePath('data.scheduled_time')
                    ->required()
                    ->hidden(fn () => ! $this->isScheduling)
                    ->live(false)
                    ->helperText(function () {
                        $userTimezone = auth()->user()->timezone ?? config('app.timezone');

                        return "Enter time in your timezone ({$userTimezone})";
                    })
                    ->extraInputAttributes(['autocomplete' => 'off']) // Prevent browser autocomplete
                    ->columnSpanFull(),

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
                    ->columnSpanFull(),

                CheckboxList::make('data.regions')
                    ->label('Target Regions')
                    ->helperText('Only send to users in selected regions. Leave empty to send to all regions.')
                    ->options(Region::query()->orderBy('name')->pluck('name', 'id'))
                    ->gridDirection('row')
                    ->columnSpanFull(),

                Toggle::make('data.test_mode')
                    ->label('Test Mode (Send to yourself only)')
                    ->helperText('When enabled, SMS will only be sent to user ID 1 for testing')
                    ->hidden(fn () => auth()->id() !== 1)
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
            ->whereHas('concierge.bookings', function (\Illuminate\Contracts\Database\Query\Builder $query) {
                $query->whereNotNull('confirmed_at');
            })
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        // Active concierges without bookings
        $conciergesActiveNoBookingsQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
            ->whereDoesntHave('concierge.bookings', function (\Illuminate\Contracts\Database\Query\Builder $query) {
                $query->whereNotNull('confirmed_at');
            })
            ->whereHas('authentications', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($sevenDaysAgo) {
                $query->where('login_at', '>=', $sevenDaysAgo);
            })
            ->whereNotNull('secured_at')
            ->whereNotNull('phone');

        // Inactive concierges
        $conciergesInactiveQuery = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
            ->whereDoesntHave('authentications', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($sevenDaysAgo) {
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
            $recipientCount = $recipients->count();

            if ($recipientCount === 0) {
                Notification::make()
                    ->title('Error')
                    ->body('No recipients selected or match the criteria')
                    ->danger()
                    ->send();

                return;
            }

            // Check if test mode is enabled
            $testMode = $this->data['test_mode'] ?? false;

            if ($testMode) {
                // In test mode, only send to user ID 1 (yourself)
                $testUser = User::query()->find(1);

                if ($testUser && $testUser->phone) {
                    logger()->info('TEST MODE: SMS will only be sent to user ID 1');
                    $recipients = collect([$testUser]);
                    $recipientCount = 1;
                } else {
                    Notification::make()
                        ->title('Error')
                        ->body('Test user (ID 1) not found or has no phone number.')
                        ->danger()
                        ->send();

                    return;
                }
            }

            // Log total count and recipient details
            logger()->info('SMS Recipients Count: '.$recipientCount);
            logger()->info('SMS Recipients:', $recipients->map(fn ($recipient) => [
                'name' => $recipient->first_name.' '.$recipient->last_name,
                'phone' => $recipient->phone,
                'role' => $testMode ? 'TEST_USER' : ($recipient->role_type ?? 'unknown'),
            ])->toArray());

            $phoneNumbers = $recipients->pluck('phone')->filter()->unique();
            $phoneNumberChunks = $phoneNumbers->chunk(50)->map->toArray()->toArray();

            // Handle scheduled SMS
            if ($this->isScheduling && filled($this->data['scheduled_time'])) {
                // Parse the time from the form in the user's timezone
                $userTimezone = auth()->user()->timezone ?? config('app.timezone');
                $localScheduledTime = Carbon::parse($this->data['scheduled_time'], $userTimezone);

                // Convert to UTC for storage
                $utcScheduledTime = $localScheduledTime->copy()->setTimezone('UTC');

                // Validate it's at least 5 minutes in the future (comparing in user's timezone)
                $minValidTime = now()->timezone($userTimezone)->addMinutes(5);

                if ($localScheduledTime->lessThan($minValidTime)) {
                    $minutesNeeded = max(5, $minValidTime->diffInMinutes($localScheduledTime) + 5);

                    Notification::make()
                        ->title('Error')
                        ->body("Please select a time at least {$minutesNeeded} minutes in the future.")
                        ->danger()
                        ->send();

                    return;
                }

                // Get current time in user's timezone
                $currentInUserTimezone = now()->setTimezone($userTimezone);

                // Log all info for debugging
                logger()->info('Scheduled SMS times:', [
                    'raw_input' => $this->data['scheduled_time'],
                    'local_time' => $localScheduledTime->toDateTimeString(),
                    'utc_time' => $utcScheduledTime->toDateTimeString(),
                    'user_timezone' => $userTimezone,
                    'current_in_user_tz' => $currentInUserTimezone->toDateTimeString(),
                ]);

                // Create a scheduled SMS record
                $scheduledSms = ScheduledSms::query()->create([
                    'message' => $message,
                    'scheduled_at' => $localScheduledTime, // User's local time
                    'scheduled_at_utc' => $utcScheduledTime, // UTC time for processing
                    'status' => 'scheduled',
                    'recipient_data' => $phoneNumberChunks,
                    'regions' => $this->data['regions'] ?? [],
                    'created_by' => auth()->id(),
                    'total_recipients' => $phoneNumbers->count(),
                    'meta' => [
                        'test_mode' => $testMode,
                    ],
                ]);

                // Build a clear, accurate message showing current time vs scheduled time
                $currentUserTime = now()->setTimezone($userTimezone);

                Notification::make()
                    ->title('Success')
                    ->body(sprintf(
                        'SMS scheduled for %s (%s). Current time there: %s. UTC time: %s',
                        $localScheduledTime->format('g:i A'),
                        $userTimezone,
                        $currentUserTime->format('g:i A'),
                        $utcScheduledTime->format('g:i A')
                    ))
                    ->success()
                    ->send();
            } else {
                // Send immediately
                foreach ($phoneNumberChunks as $chunk) {
                    dispatch(new SendBulkSmsJob($chunk, $message));
                }

                Notification::make()
                    ->title('Success')
                    ->body('SMS messages have been queued for immediate sending')
                    ->success()
                    ->send();
            }

            // Reset the form
            $this->isScheduling = false;
            $this->form->fill();

        } catch (Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to process SMS: '.$e->getMessage())
                ->danger()
                ->send();

            logger()->error('SMS Error: '.$e->getMessage(), [
                'exception' => $e,
                'data' => $this->data,
            ]);
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
                ->whereHas('concierge.bookings', function (\Illuminate\Contracts\Database\Query\Builder $query) {
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
                ->whereDoesntHave('concierge.bookings', function (\Illuminate\Contracts\Database\Query\Builder $query) {
                    $query->whereNotNull('confirmed_at');
                })
                ->whereHas('authentications', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($sevenDaysAgo) {
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
                ->whereDoesntHave('authentications', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($sevenDaysAgo) {
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

    public function table(Table $table): Table
    {
        return $table
            ->query(ScheduledSms::query()->where('status', '!=', 'cancelled'))
            ->defaultSort('scheduled_at_utc', 'desc')
            ->columns([
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(20)
                    ->size('xs'),
                TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->numeric()
                    ->sortable()
                    ->size('xs'),
                TextColumn::make('scheduled_at_utc')
                    ->label('Scheduled')
                    ->dateTime('M j, g:i A')
                    ->timezone(auth()->user()->timezone)
                    ->sortable()
                    ->size('xs'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'processing' => 'info',
                        'sent' => 'success',
                        'cancelled' => 'gray',
                        'failed' => 'danger',
                    })
                    ->size('xs'),
                TextColumn::make('creator.name')
                    ->label('By')
                    ->size('xs'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, g:i A')
                    ->timezone(auth()->user()->timezone)
                    ->sortable()
                    ->size('xs'),
            ])
            ->filters([])
            ->actions([
                Action::make('send_now')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledSms $record): bool => $record->status === 'scheduled')
                    ->action(function (ScheduledSms $record): void {
                        ProcessScheduledSmsJob::dispatch($record->id);

                        Notification::make()
                            ->title('Success')
                            ->body('SMS has been queued for immediate sending')
                            ->success()
                            ->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledSms $record): bool => $record->status === 'scheduled')
                    ->action(function (ScheduledSms $record): void {
                        $record->update(['status' => 'cancelled']);

                        Notification::make()
                            ->title('Cancelled')
                            ->body('Scheduled SMS has been cancelled')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('cancel_selected')
                    ->label('Cancel Selected')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $records = $records->filter(fn (ScheduledSms $record) => $record->status === 'scheduled');

                        $count = $records->count();
                        if ($count === 0) {
                            Notification::make()
                                ->title('No Action')
                                ->body('No scheduled SMS messages were found to cancel')
                                ->info()
                                ->send();

                            return;
                        }

                        $records->each(fn (ScheduledSms $record) => $record->update(['status' => 'cancelled']));

                        Notification::make()
                            ->title('Success')
                            ->body("Cancelled {$count} scheduled SMS messages")
                            ->success()
                            ->send();
                    }),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
