<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// #[OpenApi\PathItem]
class UpdatePushTokenController extends Controller
{
    /**
     * Update the Expo push token for the authenticated user.
     */
    //    #[OpenApi\Operation(
    //        tags: ['Update Push Tokens'],
    //        security: 'BearerTokenSecurityScheme'
    //    )]
    //    #[RequestBody(factory: PushTokenRequestBody::class)]
    //    #[OpenApiResponse(factory: MessageResponse::class)]
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
