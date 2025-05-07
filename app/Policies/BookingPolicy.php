<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // If user has direct permission to view any booking
        if ($user->can('view_any_booking')) {
            return true;
        }
        
        // Allow venue managers to view bookings
        if ($user->hasActiveRole('venue_manager') && $user->managedVenueGroups->count() > 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        // If user has direct permission to view bookings
        if ($user->can('view_booking')) {
            return true;
        }

        // Check if user is a venue manager and the booking is at one of their venues
        if ($user->hasActiveRole('venue_manager')) {
            // Get all venue groups the user manages
            $venueGroups = $user->managedVenueGroups;
            
            if ($venueGroups && $venueGroups->count() > 0) {
                foreach ($venueGroups as $venueGroup) {
                    // Get allowed venue IDs for this manager in this group
                    $allowedVenueIds = $venueGroup->getAllowedVenueIds($user);
                    
                    // Load the venue relation if it hasn't been loaded yet
                    if (!$booking->relationLoaded('venue')) {
                        $booking->load('venue');
                    }
                    
                    // Check if venue is null - this would indicate a problem with the relationship
                    if (!$booking->venue) {
                        // Try to find venue ID from meta if available
                        if (isset($booking->meta['venue']['id'])) {
                            $venueId = $booking->meta['venue']['id'];
                            
                            // If no specific venues are set, check against venues in the group
                            if (empty($allowedVenueIds)) {
                                // Check if this venue ID belongs to the venue group
                                $groupVenueIds = $venueGroup->venues()->pluck('id')->toArray();
                                if (in_array($venueId, $groupVenueIds)) {
                                    return true;
                                }
                            } 
                            // Otherwise, check directly against allowed venues list
                            elseif (in_array($venueId, $allowedVenueIds)) {
                                return true;
                            }
                        }
                    } else {
                        // If no specific venues are set, the manager can access all venues in the group
                        if (empty($allowedVenueIds)) {
                            if ($booking->venue->venue_group_id === $venueGroup->id) {
                                return true;
                            }
                        } 
                        // Otherwise, check if the booking's venue is in the allowed list
                        elseif (in_array($booking->venue->id, $allowedVenueIds)) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_booking');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->can('update_booking');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->can('delete_booking');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_booking');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        return $user->can('force_delete_booking');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_booking');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Booking $booking): bool
    {
        return $user->can('restore_booking');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_booking');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Booking $booking): bool
    {
        return $user->can('replicate_booking');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_booking');
    }
}
