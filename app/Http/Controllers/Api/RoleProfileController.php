<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleProfileController extends Controller
{
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

    public function switch(Request $request, RoleProfile $profile): JsonResponse
    {
        return response()->json([
            'message' => 'Role switching is currently disabled',
        ], 403);
    }
}
