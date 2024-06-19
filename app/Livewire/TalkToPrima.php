<?php

namespace App\Livewire;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * @property Form $form
 */
class TalkToPrima extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.talk-to-prima';

    public ?array $data = null;

    public bool $hasSent = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->hiddenLabel()
                ->placeholder('Your Name')
                ->columnSpan(1),
            TextInput::make('email')
                ->placeholder('Your Email')
                ->hiddenLabel(),
            PhoneInput::make('phone')
                ->columnSpan(2)
                ->hiddenLabel(),
            Textarea::make('message')
                ->hiddenLabel()
                ->required()
                ->placeholder('Send us a brief message about why you want to talk to PRIMA.')
                ->columnSpan(2),
        ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        Mail::raw('Name: '.$data['name']."\n\n".
                  'Email: '.$data['email']."\n\n".
                  'Phone: '.$data['phone']."\n\n".
                  'Message: '.$data['message'], static function (Message $message) {
                      $message->to('andru.weir@gmail.com')
                          ->subject('Talk to PRIMA Form Submission');
                  });

        $this->hasSent = true;
    }
}
