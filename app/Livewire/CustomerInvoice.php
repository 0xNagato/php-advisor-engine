<?php

namespace App\Livewire;

use App\Models\Booking;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;

class CustomerInvoice extends Component implements HasForms
{
    use InteractsWithForms;

    public Booking $booking;

    public $download = false;

    public $customerInvoice = true;

    public bool $emailed = false;

    public bool $emailOpen = false;

    public string $email;

    public function mount(string $token): void
    {
        $this->booking = Booking::where('uuid', $token)->firstOrFail();
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.customer-invoice');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email Address')
                    ->prefixIcon('gmdi-mail-o')
                    ->email()
                    ->placeholder('Enter your email address')
                    ->hiddenLabel()
                    ->required(),
            ]);
    }

    public function showEmailForm(): void
    {
        $this->emailOpen = ! $this->emailOpen;
    }

    public function emailInvoice(): void
    {
        $invoicePath = $this->booking->invoice_path;

        $mailable = new \App\Mail\CustomerInvoice($this->booking);
        $mailable->attachFromStorageDisk('do', $invoicePath)
            ->from('welcome@primavip.co', 'PRIMA');

        Mail::to($this->email)
            ->send($mailable);
        $this->emailOpen = false;
        $this->emailed = true;

        Notification::make()
            ->title('Invoice sent to '.$this->email)
            ->success()
            ->send();
    }
}
