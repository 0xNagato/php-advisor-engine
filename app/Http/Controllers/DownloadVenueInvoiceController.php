<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateVenueInvoice;
use App\Models\User;
use App\Models\VenueInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DownloadVenueInvoiceController extends Controller
{
    public function __invoke(User $user, string $startDate, string $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $invoice = VenueInvoice::query()
            ->where('venue_id', $user->venue->id)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->whereBetween('end_date', [$startDate, $endDate])
            ->first();

        if (! $invoice) {
            $invoice = GenerateVenueInvoice::run($user, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        }

        throw_unless(Storage::disk('do')->exists($invoice->pdf_path), new RuntimeException('Invoice PDF not found'));

        return Storage::disk('do')->download(
            $invoice->pdf_path,
            $invoice->name().'.pdf'
        );
    }
}
