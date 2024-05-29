<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ $message->announcement->title }}
        </x-slot>

        <x-slot name="headerEnd">
            <div class="text-sm text-gray-500">
                {{ $message->created_at->format('M j h:ia') }}
            </div>
        </x-slot>
        <div class="flex flex-col gap-4">
            {{ $message->announcement->message }}

            @if (isset($message->announcement->call_to_action_title, $message->announcement->call_to_action_title))
                <x-filament::button tag="a" :href="$message->announcement->call_to_action_url" target="_blank">
                    {{ $message->announcement->call_to_action_title }}
                </x-filament::button>
            @endunless
    </div>

</x-filament::section>

<x-filament::button tag="a" color="indigo" class="w-full -mt-4" :href="route('filament.admin.resources.messages.index')">
    Back
</x-filament::button>

</x-filament-panels::page>
