<div wire:poll
     class="border {{ $this->color() }} p-4 rounded-lg flex items-center gap-1">
    <div class="font-semibold">
        Booking Status:
    </div>
    <div>
        {{ $this->status() }}
    </div>
</div>
