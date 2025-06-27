<?php

use App\Models\CoverManagerReservation;
use App\Models\PlatformReservation;
use App\Models\RestooReservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate CoverManager reservations
        if (DB::getSchemaBuilder()->hasTable('cover_manager_reservations')) {
            $this->migrateCoverManagerReservations();
        }

        // Migrate Restoo reservations
        if (DB::getSchemaBuilder()->hasTable('restoo_reservations')) {
            $this->migrateRestooReservations();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the platform_reservations table
        PlatformReservation::truncate();
    }

    private function migrateCoverManagerReservations(): void
    {
        CoverManagerReservation::chunk(100, function ($reservations) {
            foreach ($reservations as $reservation) {
                PlatformReservation::create([
                    'venue_id' => $reservation->venue_id,
                    'booking_id' => $reservation->booking_id,
                    'platform_type' => 'covermanager',
                    'platform_reservation_id' => $reservation->covermanager_reservation_id,
                    'platform_status' => $reservation->covermanager_status,
                    'synced_to_platform' => $reservation->synced_to_covermanager,
                    'last_synced_at' => $reservation->last_synced_at,
                    'platform_data' => [
                        'reservation_date' => $reservation->reservation_date?->format('Y-m-d'),
                        'reservation_time' => $reservation->reservation_time,
                        'customer_name' => $reservation->customer_name,
                        'customer_email' => $reservation->customer_email,
                        'customer_phone' => $reservation->customer_phone,
                        'party_size' => $reservation->party_size,
                        'notes' => $reservation->notes,
                        'covermanager_response' => $reservation->covermanager_response,
                    ],
                    'created_at' => $reservation->created_at,
                    'updated_at' => $reservation->updated_at,
                ]);
            }
        });
    }

    private function migrateRestooReservations(): void
    {
        RestooReservation::chunk(100, function ($reservations) {
            foreach ($reservations as $reservation) {
                PlatformReservation::create([
                    'venue_id' => $reservation->venue_id,
                    'booking_id' => $reservation->booking_id,
                    'platform_type' => 'restoo',
                    'platform_reservation_id' => $reservation->restoo_reservation_id,
                    'platform_status' => $reservation->restoo_status,
                    'synced_to_platform' => $reservation->synced_to_restoo,
                    'last_synced_at' => $reservation->last_synced_at,
                    'platform_data' => [
                        'reservation_datetime' => $reservation->reservation_datetime?->toISOString(),
                        'customer_name' => $reservation->customer_name,
                        'customer_email' => $reservation->customer_email,
                        'customer_phone' => $reservation->customer_phone,
                        'party_size' => $reservation->party_size,
                        'notes' => $reservation->notes,
                        'restoo_response' => $reservation->restoo_response,
                    ],
                    'created_at' => $reservation->created_at,
                    'updated_at' => $reservation->updated_at,
                ]);
            }
        });
    }
};
