<div
    wire:poll.5s
    class="text-xs border p-2 rounded-lg flex items-center gap-2 bg-white border-slate-200">

    <div class="loading loading-ring loading-lg text-success">

    </div>

    <div>
        {{ $this->status() }}
    </div>

</div>
