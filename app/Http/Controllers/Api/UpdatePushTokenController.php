<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class UpdatePushTokenController extends Controller
{
    /**
     * Update the Expo push token for the authenticated user.
     */
    #[OpenApi\Operation(
        tags: ['Update Push Tokens'],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'push_token' => ['required', 'string'],
        ]);

        $request->user()->update([
            'expo_push_token' => $request->push_token,
        ]);

        return response()->json(['message' => 'Push token updated successfully']);
    }
}
