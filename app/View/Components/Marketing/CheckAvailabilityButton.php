<?php

namespace App\View\Components\Marketing;

use App\Models\VipCode;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class CheckAvailabilityButton extends Component
{
    public ?VipCode $vipCode;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->vipCode = Cache::remember(
            'available_calendar_button_vip_code_1',
            60,
            fn () => VipCode::query()->where('concierge_id', 1)->active()->first()
        );
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.marketing.check-availability-button', [
            'vipCode' => $this->vipCode,
        ]);
    }
}
