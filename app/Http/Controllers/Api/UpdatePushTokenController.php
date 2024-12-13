<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdatePushTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'push_token' => 'required|string',
        ]);

        $request->user()->update([
            'expo_push_token' => $request->push_token,
        ]);

        return response()->json(['message' => 'Push token updated successfully']);
    }
}
