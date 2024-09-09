<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingCheckoutController extends Controller
{
    public function __invoke(Booking $booking, Request $request)
    {
        return view('booking.checkout', [
            'booking' => $booking,
        ]);
    }
}
