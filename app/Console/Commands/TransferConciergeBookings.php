<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransferConciergeBookings extends Command
{
    protected $signature = 'concierge:transfer-bookings
                          {from : Source user ID}
                          {to : Target user ID}';

    protected $description = 'Transfer all bookings and earnings from one concierge account to another (using user IDs)';

    public function handle(): int
    {
        $sourceUserId = $this->argument('from');
        $targetUserId = $this->argument('to');

        $sourceConcierge = Concierge::query()
            ->with('user')
            ->where('user_id', $sourceUserId)
            ->first();

        $targetConcierge = Concierge::query()
            ->with('user')
            ->where('user_id', $targetUserId)
            ->first();

        if (! $sourceConcierge || ! $targetConcierge) {
            $this->error('One or both concierges not found for the given user IDs!');

            return 1;
        }

        if ($this->confirmTransfer($sourceConcierge, $targetConcierge)) {
            try {
                DB::beginTransaction();

                // Get all bookings for source concierge
                $bookings = Booking::query()->where('concierge_id', $sourceConcierge->id)->get();
                $this->info("Found {$bookings->count()} bookings to transfer");

                foreach ($bookings as $booking) {
                    // Update booking concierge
                    $booking->update(['concierge_id' => $targetConcierge->id]);

                    // Transfer associated earnings
                    Earning::query()->where('booking_id', $booking->id)
                        ->where('user_id', $sourceConcierge->user_id)
                        ->update(['user_id' => $targetConcierge->user_id]);
                }

                // Transfer any remaining earnings
                Earning::query()->where('user_id', $sourceConcierge->user_id)
                    ->whereIn('type', ['concierge', 'concierge_bounty'])
                    ->update(['user_id' => $targetConcierge->user_id]);

                DB::commit();

                $this->info('Successfully transferred all bookings and earnings');

                // Log the transfer
                activity()
                    ->performedOn($targetConcierge)
                    ->withProperties([
                        'source_concierge_id' => $sourceConcierge->id,
                        'target_concierge_id' => $targetConcierge->id,
                        'bookings_transferred' => $bookings->count(),
                    ])
                    ->log('Concierge bookings and earnings transferred');

                return 0;
            } catch (Throwable $e) {
                DB::rollBack();
                $this->error("Error during transfer: {$e->getMessage()}");

                return 1;
            }
        } else {
            return 1;
        }
    }

    private function confirmTransfer(Concierge $source, Concierge $target): bool
    {
        $this->info('About to transfer bookings and earnings:');
        $this->info("FROM: {$source->user->name} (ID: {$source->id})");
        $this->info("TO: {$target->user->name} (ID: {$target->id})");

        return $this->confirm('Do you wish to continue?');
    }
}
