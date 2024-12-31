<?php

namespace App\View\Components;

use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TypeAhead extends Component
{
    public function __construct(
        public string $label,
        public string $placeholder,
        public array|Collection $items,
        public string $valueField = 'id',
        public string $displayField = 'name',
        public ?string $wireModel = null,
        public ?string $error = null
    ) {}

    public function render()
    {
        return view('components.type-ahead');
    }
}
