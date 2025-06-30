@php
    $link = \App\Actions\GenerateVenueAgreementLink::run($onboarding);
@endphp

<div x-data="{
    copied: false,
    link: '{{ $link }}',
    copyToClipboard() {
        navigator.clipboard.writeText(this.link);
        this.copied = true;
        setTimeout(() => this.copied = false, 2000);
    }
}">
    <div class="p-4 mb-4 rounded-md bg-blue-50 border border-blue-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm text-blue-700">
                    This link allows the venue to view and download their agreement without needing to login. 
                    The link expires after 30 days.
                </p>
            </div>
        </div>
    </div>
    <div class="flex flex-col space-y-2">
        <label for="agreement-link" class="text-sm font-medium text-gray-700">Agreement Link</label>
        <div class="flex mt-1 rounded-md shadow-sm">
            <textarea 
                id="agreement-link" 
                x-text="link" 
                class="block w-full flex-1 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                rows="2"
                readonly
            ></textarea>
        </div>
        <div class="flex justify-end">
            <div x-show="!copied">
                <x-filament::button @click="copyToClipboard()" color="primary" icon="heroicon-m-clipboard">
                    Copy to Clipboard
                </x-filament::button>
            </div>
            <div x-show="copied" x-cloak>
                <x-filament::button color="success" icon="heroicon-m-check">
                    Copied!
                </x-filament::button>
            </div>
        </div>
    </div>
</div>