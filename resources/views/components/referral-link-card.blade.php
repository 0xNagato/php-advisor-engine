<div class="p-6 bg-white rounded-lg shadow filament-card">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Your Referral Link</h3>
    <p class="mt-1 text-sm text-gray-500">
        Share this link with other concierges to invite them to join PRIMA. You'll earn commissions on their bookings.
    </p>
    <div x-data="{
        copied: false,
        copyToClipboard() {
            navigator.clipboard.writeText('{{ $referralUrl }}');
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    }" class="flex items-center mt-3 gap-x-3">
        <input type="text" value="{{ $referralUrl }}"
            class="flex-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
            readonly>
        <button @click="copyToClipboard()" type="button"
            class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak class="text-green-600">Copied!</span>
        </button>
    </div>
</div>
