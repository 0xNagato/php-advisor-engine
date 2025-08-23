<?php

namespace App\Livewire;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SiteContactForm extends Component
{
    protected static string $view = 'livewire.site-contact-form';

    public array $data = [
        'persona' => '',
        'fullName' => '',
        'company' => '',
        'email' => '',
        'phone' => '',
        'city' => '',
        'preferredTime' => '',
        'notes' => '',
    ];

    public bool $hasSent = false;

    protected $rules = [
        'data.persona' => 'required',
        'data.fullName' => 'required|string|max:255',
        'data.email' => 'required|email|max:255',
        'data.company' => 'nullable|string|max:255',
        'data.phone' => 'nullable|string|max:255',
        'data.city' => 'nullable|string|max:255',
        'data.preferredTime' => 'nullable|string|max:255',
        'data.notes' => 'nullable|string|max:1000',
    ];

        public function send(): void
    {
        $this->validate();

        $emailBody = "Site Contact Form Submission:\n\n" .
            "I am a: " . ucfirst($this->data['persona']) . "\n\n" .
            "Full Name: " . $this->data['fullName'] . "\n\n" .
            "Email: " . $this->data['email'] . "\n\n" .
            "Phone: " . ($this->data['phone'] ?? 'Not provided') . "\n\n" .
            "Company/Property: " . ($this->data['company'] ?? 'Not provided') . "\n\n" .
            "City: " . ($this->data['city'] ?? 'Not provided') . "\n\n" .
            "Preferred Contact Time: " . ($this->data['preferredTime'] ?? 'Not provided') . "\n\n" .
            "Notes: " . ($this->data['notes'] ?? 'Not provided');

        Mail::raw($emailBody, static function (Message $message) {
            $message
                ->to('prima@primavip.co')
                ->cc('kevin@primavip.co')
                ->cc('alex@primavip.co')
                ->bcc('andru.weir@gmail.com')
                ->subject('Site Contact Form Submission');
        });

        $this->hasSent = true;
    }

    public function resetForm(): void
    {
        $this->hasSent = false;
        $this->data = [
            'persona' => '',
            'fullName' => '',
            'company' => '',
            'email' => '',
            'phone' => '',
            'city' => '',
            'preferredTime' => '',
            'notes' => '',
        ];
    }

    public function render()
    {
        return view('livewire.site-contact-form');
    }
}
