<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class MeController extends Controller
{
    /**
     * Retrieve the authenticated user's profile data.
     */
    #[OpenApi\Operation(
        tags: ['Me'],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'role' => Str::of($user->main_role)->snake(),
                    'email' => $user->email,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'timezone' => $user->timezone,
                    'region' => $user->region,
                ],
            ],
        ]);
    }
}
