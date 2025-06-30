<x-layouts.simple-wrapper>
    <div class="max-w-xl mx-auto mt-8">
        <div class="mb-8 text-center">
            <h2 class="text-2xl font-bold text-gray-900">Venue Onboarding</h2>
            <p class="mt-2 text-gray-600">
                Thank you for your interest in onboarding your venue with PRIMA. 
                Our team is currently reviewing applications on a limited basis.
                Please fill out the form below to let us know about your venue, and we'll get back to you as soon as possible.
            </p>
        </div>

        <div class="p-6 bg-white rounded-lg shadow">
            <form wire:submit.prevent="submitContactForm" class="space-y-6">
                <div class="space-y-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" wire:model="contact.name" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Your full name">
                        @error('contact.name')
                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" wire:model="contact.email" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Your email address">
                        @error('contact.email')
                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" wire:model="contact.phone" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Your phone number">
                        @error('contact.phone')
                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Region</label>
                        <select wire:model="contact.region" 
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (\App\Models\Region::active()->get() as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                        @error('contact.region')
                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Venue Name</label>
                        <input type="text" wire:model="contact.venue_name" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Name of your venue">
                        @error('contact.venue_name')
                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Message</label>
                        <textarea wire:model="contact.message" rows="4" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Tell us a bit about your venue and any questions you might have"></textarea>
                        @error('contact.message')
                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full px-6 py-3 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Submit Inquiry
                    </button>
                </div>
            </form>

            @if($formSubmitted)
            <div class="mt-6 p-4 rounded-lg bg-green-50 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Inquiry Received</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Thank you for your interest in PRIMA! Our team will review your information and be in touch with you shortly.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-layouts.simple-wrapper>