<?php

namespace App\Livewire;

use Ijpatricio\Mingle\Concerns\InteractsWithMingles;
use Ijpatricio\Mingle\Contracts\HasMingles;
use Livewire\Component;

class ComicStrip extends Component implements HasMingles
{
    use InteractsWithMingles;

    public array $pages;

    public int $currentPage = 0;

    public function mount(array $pages): void
    {
        $this->pages = $pages;
    }

    public function component(): string
    {
        return 'resources/js/ComicStrip/index.js';
    }

    public function mingleData(): array
    {
        return [
            'pages' => $this->pages,
            'currentPage' => $this->currentPage,
        ];
    }

    public function nextPage(): void
    {
        $this->currentPage = ($this->currentPage + 1) % count($this->pages);
    }

    public function prevPage(): void
    {
        $this->currentPage = ($this->currentPage - 1 + count($this->pages)) % count($this->pages);
    }
}
