<div class="space-y-4">
    <!-- Agreement Content -->
    <div class="p-4 overflow-y-auto border border-gray-200 rounded-lg bg-gray-50 max-h-[60vh]">
        @include('partials.venue-agreement', [
            'company_name' => $onboarding->company_name,
            'venue_names' => $onboarding->locations->pluck('name')->toArray(),
            'first_name' => $agreement_accepted ? $first_name : $onboarding->first_name,
            'last_name' => $agreement_accepted ? $last_name : $onboarding->last_name,
            'use_non_prime_incentive' => $onboarding->use_non_prime_incentive,
            'non_prime_per_diem' => $onboarding->non_prime_per_diem,
            'created_at' => $onboarding->created_at,
        ])
    </div>

    <!-- Success Message -->
    @if($successMessage)
        <div class="p-4 mb-4 rounded-md bg-green-50 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ $successMessage }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Contact Information Form -->
    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Contact Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                <input wire:model.live="first_name" type="text" id="first_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                @error('first_name') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                <input wire:model.live="last_name" type="text" id="last_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                @error('last_name') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input wire:model.live="email" type="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                @error('email') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input wire:model.live="phone" type="text" id="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                @error('phone') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mt-4">
            <label class="flex items-center">
                <input wire:model.live="agreement_accepted" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-600">I accept the terms of this agreement</span>
            </label>
            @error('agreement_accepted') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </div>
    </div>

    <!-- Actions - Only show when form has been filled and agreement accepted -->
    @if(filled($first_name) && filled($last_name) && filled($email) && filled($phone) && $agreement_accepted)
        <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3 sm:justify-center mt-6">
            <button wire:click="downloadAgreement" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                Download Agreement
            </button>

            <button wire:click="emailAgreement" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
                Send to Email
            </button>
        </div>
    @else
        <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-md">
            <p class="text-center text-sm text-gray-600">
                Please fill out your contact information and accept the agreement to proceed.
            </p>
        </div>
    @endif

    <!-- JavaScript to handle the download after form validation -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('download-agreement', (params) => {
                const encryptedId = params.encryptedId;
                window.location.href = `/venue/public-agreement-download/${encryptedId}`;
            });
        });
    </script>
</div>
