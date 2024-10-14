<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use App\Models\Message;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/**
 * @property Form $form
 */
class ListMessages extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MessageResource::class;

    protected ?string $heading = 'Announcements';

    protected static string $view = 'filament.pages.messages.list-messages';

    /**
     * @var Collection<Message>
     */
    public Collection $messages;

    public ?array $contactData = [];

    public function mount(): void
    {
        $this->messages = Message::with('announcement.sender')
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('message')
                    ->required()
                    ->maxLength(500)
                    ->hiddenLabel()
                    ->placeholder('How can we help you?')
                    ->label('Your Message')
                    ->helperText('Please provide as much detail as possible.')
                    ->rows(5),
            ])
            ->statePath('contactData');
    }

    public function submitContactForm(): void
    {
        $data = $this->contactData;

        Mail::send([], [], function ($message) use ($data) {
            $message->to('alex@primavip.co')
                ->bcc('andru.weir@gmail.com')
                ->subject('New message from ' . auth()->user()->name)
                ->html($data['message']);
        });

        Notification::make()
            ->title('Message sent')
            ->success()
            ->send();

        $this->form->fill();
    }
}
