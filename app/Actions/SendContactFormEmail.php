<?php

namespace App\Actions;

use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;

class SendContactFormEmail
{
    use AsAction;

    public function handle(array $data, $user)
    {
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

        return true;
    }
}
