<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FileUpload extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $model = null,
        public ?object $file = null,
        public ?string $error = null,
        public bool $showDelete = true
    ) {}

    public function render()
    {
        return view('components.file-upload');
    }
}
