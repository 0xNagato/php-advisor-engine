<?php

namespace App\Actions\SpecialRequest;

use App\Data\RestaurantContactData;
use App\Data\SpecialRequest\CreateSpecialRequestData;
use App\Models\SpecialRequest;
use App\Notifications\Restaurant\SendSpecialRequestConfirmation;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

class CreateSpecialRequest
{
    use AsAction;

    public function handle(CreateSpecialRequestData $data): SpecialRequest
    {
        $specialRequest = SpecialRequest::query()->create($data->toArray());

        $this->sendMessagesToContacts($specialRequest->restaurant->contacts, $specialRequest);

        return $specialRequest;
    }

    /**
     * Send the message to the restaurant contacts.
     *
     * @param  DataCollection<RestaurantContactData>  $contacts
     */
    private function sendMessagesToContacts(DataCollection $contacts, SpecialRequest $specialRequest): void
    {
        foreach ($contacts as $contact) {
            if ($contact->use_for_reservations) {
                $contact->notify(new SendSpecialRequestConfirmation($specialRequest));
            }
        }
    }
}
