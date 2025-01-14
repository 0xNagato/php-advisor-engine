<x-filament-widgets::widget>

    <div class="flex border-b border-gray-200">
        <button wire:click="setActiveTab('sms')"
            class="px-3 py-1.5 text-sm font-semibold {{ $activeTab === 'sms' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500' }}">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="gmdi-phone-android-o" class="w-4 h-4" />
                Send SMS
            </div>
        </button>
        <button wire:click="setActiveTab('email')"
            class="px-3 py-1.5 text-sm font-semibold {{ $activeTab === 'email' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500' }}">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="gmdi-email-o" class="w-4 h-4" />
                Send Email
            </div>
        </button>
    </div>

    <div class="mt-4">
        @if ($activeTab === 'sms')
            {{ $this->smsForm }}
        @else
            {{ $this->emailForm }}
        @endif
    </div>
</x-filament-widgets::widget>
