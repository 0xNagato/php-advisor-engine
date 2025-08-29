<?php

namespace App\Livewire\Concierge;

use App\Models\VipCode;
use Livewire\Component;

class VipCodeCollectionsManager extends Component
{
    public VipCode $vipCode;

    public function mount(VipCode $vipCode): void
    {
        $this->vipCode = $vipCode;
    }

    public function render()
    {
        return view('livewire.concierge.vip-code-collections-manager', [
            'vipCode' => $this->vipCode,
        ]);
    }
}
