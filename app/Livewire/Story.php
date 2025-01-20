<?php

namespace App\Livewire;

use Livewire\Component;

class Story extends Component
{
    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Story';
    }

    public function render()
    {
        return view('livewire.story');
    }
}
