<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\RoleProfile;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Application;
use Livewire\Component;

class RoleSwitcher extends Component
{
    /** @var Collection<int, RoleProfile>|null */
    public ?Collection $profiles = null;

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $this->profiles = auth()->user()->roleProfiles()->with('role')->get();
    }

    /**
     * Switch the user's active role profile.
     *
     * @param  int  $profileId  The ID of the profile to switch to
     */
    public function switchProfile(int $profileId): void
    {
        $profile = RoleProfile::query()->find($profileId);

        if (! $profile || $profile->user_id !== auth()->id()) {
            return;
        }

        auth()->user()->switchProfile($profile);

        $this->redirect('/platform');
    }

    /**
     * Render the role switcher component.
     */
    public function render(): \Illuminate\View\View|Application|Factory|View
    {
        return view('filament.admin.role-switcher');
    }
}
