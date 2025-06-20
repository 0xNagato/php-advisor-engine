<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleProfile;
use App\OpenApi\Parameters\RoleProfileParameter;
use App\OpenApi\Responses\MessageResponse;
use App\OpenApi\Responses\RoleProfileListResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Parameters;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class RoleProfileController extends Controller
{
    /**
     * Get the list of role profiles for the authenticated user.
     */
    #[OpenApi\Operation(
        tags: ['Role Profiles'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[OpenApiResponse(factory: RoleProfileListResponse::class)]
    public function index(Request $request): JsonResponse
    {
        $profiles = $request->user()
            ->roleProfiles()
            ->with('role')
            ->get()
            ->map(fn (RoleProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'role' => $profile->role->name,
                'is_active' => $profile->is_active,
            ]);

        return response()->json([
            'profiles' => $profiles,
        ]);
    }

    /**
     * Attempt to switch the active role profile.
     */
    #[OpenApi\Operation(
        tags: ['Role Profiles'],
    )]
    #[Parameters(factory: RoleProfileParameter::class)]
    #[OpenApiResponse(factory: MessageResponse::class)]
    public function switch(Request $request, RoleProfile $profile): JsonResponse
    {
        return response()->json([
            'message' => 'Role switching is currently disabled from the mobile app, please use the web app to switch roles.',
        ], 403);
    }
}
