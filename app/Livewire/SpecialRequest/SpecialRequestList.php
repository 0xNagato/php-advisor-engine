<?php

namespace App\Livewire\SpecialRequest;

use App\Models\SpecialRequest;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

class SpecialRequestList extends Widget
{
    protected static string $view = 'livewire.special-request.special-request-list';

    protected static ?string $pollingInterval = null;

    /**
     * @var Collection<int, SpecialRequest>
     */
    public Collection $specialRequests;

    public ?int $conciergeId = null;

    #[On('special-request-created')]
    public function mount(): void
    {
        $this->specialRequests = SpecialRequest::query()
            ->with('venue')
            ->when($this->conciergeId, function ($query) {
                $query->where('concierge_id', $this->conciergeId);
            })
            ->orderBy('booking_date', 'desc')
            ->get();
    }
}
