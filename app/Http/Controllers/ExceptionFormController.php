<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class ExceptionFormController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $data = $request->only(['message', 'exceptionMessage', 'exceptionTrace']);

        Feedback::create([
            'user_id' => auth()->id(),
            'message' => $data['message'],
            'exception_message' => $data['exceptionMessage'],
            'exception_trace' => $data['exceptionTrace'],
        ]);

        return redirect('/');
    }
}
