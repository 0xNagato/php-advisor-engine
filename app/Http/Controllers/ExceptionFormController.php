<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class ExceptionFormController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $data = $request->only(['message', 'exceptionMessage', 'exceptionTrace']);

        $feedback = Feedback::query()->create([
            'user_id' => auth()->id(),
            'message' => $data['message'],
            'exception_message' => $data['exceptionMessage'],
            'exception_trace' => $data['exceptionTrace'],
        ]);

        $data['feedback_id'] = $feedback->id;

        $emailContent = '';
        foreach ($data as $key => $value) {
            $emailContent .= $key.': '.$value."\n";
        }

        Mail::raw($emailContent, static function (Message $message) use ($data) {
            $message->to('andru.weir@gmail.com')
                ->from('info@primavip.co', 'PrimaVIP')
                ->sender('info@primavip.co', 'PrimaVIP')
                ->subject('Data from Exception Form: '.$data['exceptionMessage']);
        });

        return redirect('/');
    }
}
