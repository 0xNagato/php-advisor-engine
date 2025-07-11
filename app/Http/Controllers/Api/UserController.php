<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Handle the incoming request to fetch all users for super admins.
     *
     * This endpoint retrieves a list of all users along with their associated
     * concierge and partner profiles. Access is restricted to users with
     * the 'super_admin' role.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        // Restrict access to super admins only.
        if (! $user->hasActiveRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // Eager load relationships and paginate the results to improve performance
        // and provide a structured, navigable response.
        $users = User::with(['concierge', 'partner', 'roles'])->latest()->paginate();

        // When a paginator instance is returned as JSON, Laravel automatically
        // formats it into the standard structure for paginated API responses.
        return response()->json($users);
    }
}
