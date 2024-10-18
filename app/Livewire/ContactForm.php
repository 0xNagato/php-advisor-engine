<?php

namespace App\Livewire;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;

/**
 * @property Form $form
 */
class ContactForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
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
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $formattedMessage = "Name: $user->name\n";
        $formattedMessage .= "Email: $user->email\n";
        $formattedMessage .= "Phone: $user->phone\n";
        $formattedMessage .= "Role: $user->main_role\n\n";
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

        // Dispatch an event to close the modal
        $this->dispatch('close-modal', id: 'contact-us-modal');
    }

    public function render(): Application|Factory|\Illuminate\Contracts\View\View|View
    {
        return view('livewire.contact-form');
    }
}
