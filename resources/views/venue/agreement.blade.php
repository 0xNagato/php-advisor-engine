<x-layouts.app>
    <x-layouts.simple-wrapper contentClass="max-w-4xl">

        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="space-y-4">
                        <!-- Agreement Content -->
                        <div class="p-4 overflow-y-auto border border-gray-200 rounded-lg bg-gray-50 max-h-[60vh]">
                            @include('partials.venue-agreement', [
                                'company_name' => $onboarding->company_name,
                                'venue_names' => $onboarding->locations->pluck('name')->toArray(),
                                'first_name' => $onboarding->first_name,
                                'last_name' => $onboarding->last_name,
                                'use_non_prime_incentive' => $onboarding->use_non_prime_incentive,
                                'non_prime_per_diem' => $onboarding->non_prime_per_diem,
                                'created_at' => $onboarding->created_at,
                            ])
                        </div>

                        <!-- Download Button -->
                        <div class="flex justify-center mt-6">
                            <a href="{{ route('venue.agreement.public-download', ['onboarding' => request()->route('onboarding')]) }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                Download Agreement
                            </a>
                        </div>

                        <!-- Email Form -->
                        <div class="pt-6 mt-6 border-t border-gray-200">
                            @if(session('success'))
                                <div class="p-4 mb-4 rounded-md bg-green-50 border border-green-200">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-green-800">
                                                {{ session('success') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <form action="{{ route('venue.agreement.email', ['onboarding' => request()->route('onboarding')]) }}" method="POST" class="mt-4">
                                @csrf
                                <div class="mb-4">
                                    <input type="email" name="email" id="email" 
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Enter your email address" required>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                        Send to Email
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </x-layouts.simple-wrapper>
</x-layouts.app>