<?php

namespace App\Services;

use App\Enums\ManyChatCustomField;
use App\Http\Integrations\ManyChat\ManyChat;
use App\Http\Integrations\ManyChat\Requests\AddSubscriberTag;
use App\Http\Integrations\ManyChat\Requests\CreateSubscriber;
use App\Http\Integrations\ManyChat\Requests\CreateTag;
use App\Http\Integrations\ManyChat\Requests\FindSubscriberByCustomField;
use App\Http\Integrations\ManyChat\Requests\UpdateCustomFields;
use App\Http\Integrations\ManyChat\Requests\UpdateSubscriber;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class ManyChatService
{
    private readonly ManyChat $client;

    public function __construct()
    {
        $this->client = new ManyChat;
    }

    public function syncUser(User $user): bool
    {
        try {
            $response = $this->client->send(new FindSubscriberByCustomField(
                fieldId: ManyChatCustomField::USER_ID->getId(),
                fieldValue: $user->id
            ));

            if ($response->successful()) {
                $subscriber = $response->json('data.0');
                $subscriberId = $subscriber['id'] ?? null;

                return $subscriberId ?
                    $this->updateSubscriber($subscriberId, $user, $subscriber) :
                    $this->createSubscriber($user);
            }

            return false;
        } catch (Exception $e) {
            Log::error('ManyChat sync error', [
                'user' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function createSubscriber(User $user): bool
    {
        try {
            $response = $this->client->send(new CreateSubscriber(
                firstName: $user->first_name,
                lastName: $user->last_name,
                email: $user->email,
                phone: $user->phone,
                whatsappPhone: $user->phone,
            ));

            if ($response->successful()) {
                $subscriberId = $response->json('data.id');

                $tags = $this->buildTagsForUser($user);
                foreach ($tags as $tag) {
                    $this->ensureTagExists($tag);
                    $this->client->send(new AddSubscriberTag(
                        subscriberId: $subscriberId,
                        tagName: $tag
                    ));
                }

                $customFieldsResponse = $this->client->send(new UpdateCustomFields(
                    subscriberId: $subscriberId,
                    customFields: [
                        'user_id' => $user->id,
                        'region' => $user->region ?? 'miami',
                    ]
                ));

                return $customFieldsResponse->successful();
            }

            return false;
        } catch (Exception $e) {
            Log::error('ManyChat create subscriber error', [
                'user' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function updateSubscriber(string $subscriberId, User $user, array $subscriber): bool
    {
        try {
            $infoResponse = $this->client->send(new UpdateSubscriber(
                subscriberId: $subscriberId,
                firstName: $subscriber['first_name'] !== $user->first_name ? $user->first_name : null,
                lastName: $subscriber['last_name'] !== $user->last_name ? $user->last_name : null,
                whatsappPhone: ($subscriber['whatsapp_phone'] ?? null) !== $user->phone ? $user->phone : null,
                email: ($subscriber['email'] ?? null) !== $user->email ? $user->email : null,
                phone: ($subscriber['phone'] ?? null) !== $user->phone ? $user->phone : null,
            ));

            $tags = $this->buildTagsForUser($user);
            $success = true;

            foreach ($tags as $tag) {
                $tagResponse = $this->client->send(new AddSubscriberTag(
                    subscriberId: $subscriberId,
                    tagName: $tag
                ));
                $success = $success && $tagResponse->successful();
            }

            $customFieldsResponse = $this->client->send(new UpdateCustomFields(
                subscriberId: $subscriberId,
                customFields: [
                    'user_id' => $user->id,
                    'region' => $user->region ?? 'miami',
                ]
            ));

            return $infoResponse->successful() && $success && $customFieldsResponse->successful();
        } catch (Exception $e) {
            Log::error('ManyChat update subscriber error', [
                'user' => $user->id,
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function buildTagsForUser(User $user): array
    {
        $tags = [];
        $allowedRoles = ['concierge', 'venue', 'partner'];

        foreach ($user->roles as $role) {
            if (in_array($role->name, $allowedRoles)) {
                $tags[] = "role:$role->name";
            }
        }

        if ($user->region) {
            $tags[] = "region:$user->region";
        }

        return $tags;
    }

    private function ensureTagExists(string $tag): bool
    {
        try {
            $response = $this->client->send(new CreateTag($tag));

            return $response->successful();
        } catch (Exception) {
            return true;
        }
    }
}
