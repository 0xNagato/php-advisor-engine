<?php

namespace App\Livewire;

use Illuminate\Contracts\Support\Htmlable;
use Livewire\Component;

class Story extends Component
{
    public function getHeading(): string|Htmlable
    {
        return 'Story';
    }

    public function render()
    {
        return view('livewire.story');
    }
}
