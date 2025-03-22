<?php

namespace App\Livewire;

use Filament\Forms\Components\CheckboxList;
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

    protected static ?string $pollingInterval = null;

    protected static string $view = 'livewire.talk-to-prima';

    public ?array $data = null;

    public bool $hasSent = false;

    public array $reasons = [
        '<span class="font-normal">I’d like to join PRIMA as a concierge</span>',
        '<span class="font-normal">I’d like more information on listing my venue on PRIMA</span>',
        '<span class="font-normal">I’d like to explore a partnership opportunity with PRIMA</span>',
        '<span class="font-normal">I’m having trouble with something</span>',
    ];

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
                ->required()
                ->columnSpan(2)
                ->hiddenLabel(),
            CheckboxList::make('why')
                ->label('Why are you reaching out to us?')
                ->options($this->reasons)
                ->allowHtml()
                ->columnSpan(2)->columns()
                ->required()
                ->validationMessages([
                    'required' => 'You must select at least one reason.',
                ]),
            Textarea::make('message')
                ->hiddenLabel()
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

        $selectedOptions = array_map(fn ($index) => strip_tags((string) $this->reasons[$index]), $data['why']);
        $selectedOptionsText = implode(', ', $selectedOptions);

        Mail::raw('Name: '.$data['name']."\n\n".
            'Email: '.$data['email']."\n\n".
            'Phone: '.$data['phone']."\n\n".
            'Message: '.$data['message']."\n\n".
            'Reasons: '.$selectedOptionsText, static function (Message $message) {
                $message
                    ->to('prima@primavip.co')
                    ->cc('kevin@primavip.co')
                    ->cc('alex@primavip.co')
                    ->bcc('andru.weir@gmail.com')
                    ->subject('Talk to PRIMA Form Submission');
            });

        $this->hasSent = true;
    }
}
