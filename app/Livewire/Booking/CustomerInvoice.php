<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Models\Region;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;

class CustomerInvoice extends Component implements HasForms
{
    use InteractsWithForms;

    public Booking $booking;

    public Region $region;

    public $download = false;

    public $customerInvoice = true;

    public bool $emailed = false;

    public bool $emailOpen = false;

    public string $email;

    public function mount(string $token): void
    {
        $this->booking = Booking::query()->where('uuid', $token)
            ->with('earnings.user')
            ->firstOrFail();

        $this->region = Region::query()->find($this->booking->city);
    }

    public function render(): View
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
