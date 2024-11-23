<?php

namespace App\Filament\Pages\Admin;

use App\Jobs\SendBulkSmsJob;
use App\Models\Referral;
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
        return [
            'concierges' => User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
                ->whereNotNull('secured_at')
                ->whereNotNull('phone')
                ->count(),

            'pending_concierges' => Referral::query()
                ->where('type', 'concierge')
                ->whereNull('secured_at')
                ->whereNotNull('phone')
                ->count(),

            'partners' => User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'partner'))
                ->whereNotNull('secured_at')
                ->whereNotNull('phone')
                ->count(),

            'venues' => User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'venue'))
                ->whereNotNull('phone')
                ->count(),
        ];
    }

    public function send(): void
    {
        $data = $this->form->getState()['data'];
        $recipients = collect($data['recipients']);
        $message = $data['message'];
        $phoneNumbers = collect();

        if ($recipients->contains('concierges')) {
            $phoneNumbers->push(...User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'concierge'))
                ->whereNotNull('secured_at')
                ->whereNotNull('phone')
                ->pluck('phone'));
        }

        if ($recipients->contains('pending_concierges')) {
            $phoneNumbers->push(...Referral::query()
                ->where('type', 'concierge')
                ->whereNull('secured_at')
                ->whereNotNull('phone')
                ->pluck('phone'));
        }

        if ($recipients->contains('partners')) {
            $phoneNumbers->push(...User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'partner'))
                ->whereNotNull('secured_at')
                ->whereNotNull('phone')
                ->pluck('phone'));
        }

        if ($recipients->contains('pending_partners')) {
            $phoneNumbers->push(...Referral::query()
                ->where('type', 'partner')
                ->whereNull('secured_at')
                ->whereNotNull('phone')
                ->pluck('phone'));
        }

        if ($recipients->contains('venues')) {
            $phoneNumbers->push(...User::query()
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'venue'))
                ->whereNotNull('phone')
                ->pluck('phone'));
        }

        $phoneNumbers = $phoneNumbers->filter()->unique();

        try {
            $phoneNumbers->chunk(50)->each(function ($chunk) use ($message) {
                dispatch(new SendBulkSmsJob($chunk->toArray(), $message));
            });

            Notification::make()
                ->title('Success')
                ->body('SMS messages have been queued for sending')
                ->success()
                ->send();

            $this->form->fill();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to queue SMS messages: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
