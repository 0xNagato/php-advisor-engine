<?php

namespace App\Livewire\SpecialRequest;

use App\Models\SpecialRequest;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class SpecialRequestList extends Widget
{
    protected static string $view = 'livewire.special-request.special-request-list';

    /**
     * @var Collection<int, SpecialRequest>
     */
    public Collection $specialRequests;

    public function mount(int $conciergeId): void
    {
        $this->specialRequests = SpecialRequest::query()
            ->with('restaurant')
            ->where('concierge_id', $conciergeId)
            ->get();
    }
}
