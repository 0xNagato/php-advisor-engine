<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use App\Models\Message;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
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
        $user = auth()->user();

        // Create a formatted message with user details
        $formattedMessage = "Name: {$user->name}\n";
        $formattedMessage .= "Email: {$user->email}\n";
        $formattedMessage .= "Role: {$user->main_role}\n";
        $formattedMessage .= "Phone: {$user->phone}\n\n";
        $formattedMessage .= "Message:\n{$data['message']}";

        Mail::send([], [], function ($message) use ($formattedMessage, $user) {
            $message->to('alex@primavip.co')
                ->bcc('andru.weir@gmail.com')
                ->replyTo($user->email)
                ->subject('New message from '.$user->name)
                ->html(nl2br($formattedMessage));
        });

        Notification::make()
            ->title('Message sent')
            ->success()
            ->send();

        $this->form->fill();
    }
}
