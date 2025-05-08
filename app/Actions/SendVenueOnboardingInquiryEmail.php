<?php

namespace App\Actions;

use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;

class SendVenueOnboardingInquiryEmail
{
    use AsAction;

    public function handle(array $contactData)
    {
        $formattedMessage = "Name: {$contactData['name']}\n";
        $formattedMessage .= "Email: {$contactData['email']}\n";
        $formattedMessage .= "Phone: {$contactData['phone']}\n";
        $formattedMessage .= "Region: {$contactData['region']}\n";
        $formattedMessage .= "Venue Name: {$contactData['venue_name']}\n\n";
        $formattedMessage .= "Message:\n{$contactData['message']}";

        Mail::send([], [], function ($message) use ($formattedMessage, $contactData) {
            $message->to('prima@primavip.co')
                ->cc('kevin@primavip.co')
                ->cc('alex@primavip.co')
                ->bcc('andru.weir@gmail.com')
                ->replyTo($contactData['email'])
                ->subject('New Venue Onboarding Inquiry: '.$contactData['venue_name'])
                ->html(nl2br($formattedMessage));
        });

        return true;
    }
}
