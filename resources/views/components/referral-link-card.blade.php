<div class="p-6 bg-white rounded-lg shadow filament-card">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Your Referral Link</h3>
    <p class="mt-1 text-sm text-gray-500">
        @if ($type === 'venue_manager')
            Share this link with concierges to invite them to promote venues within your venue group.
        @else
            {{-- Default text for concierge and partner --}}
            Share this link with other concierges to invite them to join PRIMA. You'll earn commissions on their
            bookings.
        @endif
    </p>
    <div x-data="{
        copied: false,
        showQr: false,
        copyToClipboard() {
            navigator.clipboard.writeText('{{ $referralUrl }}');
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    }" class="mt-3">
        <div class="flex items-center gap-x-3">
            <input type="text" value="{{ $referralUrl }}"
                class="flex-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                readonly>
            <button @click="copyToClipboard()" type="button"
                class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <span x-show="!copied">Copy</span>
                <span x-show="copied" x-cloak class="text-green-600">Copied!</span>
            </button>
        </div>
        <button @click="showQr = !showQr" type="button"
            class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 w-full mt-2">
            <span class="flex items-center gap-1">
                <x-gmdi-qr-code class="w-4 h-4" />
                <span x-text="showQr ? 'Hide QR' : 'QR Code'"></span>
            </span>
        </button>
        <div x-show="showQr" x-cloak class="flex flex-col items-center mt-4">
            <p class="mb-2 text-sm text-gray-600">Scan this QR code to access your referral link:</p>
            <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                <img src="{{ $qrCode }}" alt="Referral QR Code" class="w-40 h-40">
            </div>
            <a href="{{ $qrCodeDownloadUrl }}" download="prima-referral-qr.png"
                class="mt-3 text-sm text-indigo-600 underline hover:text-indigo-800">
                Download QR Code
            </a>
        </div>
    </div>
</div>
