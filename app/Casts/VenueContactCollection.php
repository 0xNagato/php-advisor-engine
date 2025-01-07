<?php

namespace App\Casts;

use App\Data\VenueContactData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\DataCollection;

class VenueContactCollection implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): DataCollection
    {
        $contacts = json_decode($value, true) ?? [];
        $additionalPhones = array_filter(array_map('trim', explode(',', config('app.venue_booking_notification_phones') ?? '')));

        foreach ($additionalPhones as $phone) {
            $exists = false;
            foreach ($contacts as $contact) {
                if ($contact['contact_phone'] === $phone) {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                $contacts[] = [
                    'contact_name' => 'Additional Contact',
                    'contact_phone' => $phone,
                    'use_for_reservations' => true,
                ];
            }
        }

        return new DataCollection(VenueContactData::class, $contacts);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        // Filter out any "Additional Contact" entries before saving
        $contacts = collect($value)->filter(fn ($contact) => $contact['contact_name'] !== 'Additional Contact');

        return json_encode($contacts);
    }
}
