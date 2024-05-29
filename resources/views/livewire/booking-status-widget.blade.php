<div wire:poll.5s.visible class="text-xs border p-2 rounded-lg flex items-center gap-2 bg-white border-slate-200">

    <x-filament::loading-indicator class="h-5 w-5 text-green-600" />

    <div>
        {{ $this->status() }}
    </div>

</div>
