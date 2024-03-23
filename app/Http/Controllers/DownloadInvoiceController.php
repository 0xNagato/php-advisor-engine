<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\Storage;

class DownloadInvoiceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)->firstOrFail();

        $invoicePath = $booking->invoice_path;
        
        $disk = Storage::disk('do');

        if (!$disk->exists($invoicePath)) {
            abort(404, 'File not found.');
        }

        $fileContents = $disk->get($invoicePath);

        $temporaryFilePath = tempnam(sys_get_temp_dir(), 'download');
        file_put_contents($temporaryFilePath, $fileContents);

        return response()
            ->download($temporaryFilePath, basename($invoicePath))
            ->deleteFileAfterSend(true);
    }
}
