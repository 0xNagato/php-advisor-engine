<x-layouts.app>
    <x-layouts.simple-wrapper contentClass="max-w-3xl">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-8 text-gray-900">
                    <div class="space-y-2 mb-6 border-b pb-4">
                        <h1 class="text-xl font-bold">{{ $message->announcement->title }}</h1>
                        <div class="text-sm text-gray-500">
                            {{ $message->created_at->format('M j, Y · g:ia') }}
                        </div>
                    </div>

                    <div class="space-y-6 text-base">
                        <div class="prose prose-indigo max-w-none [&_a]:text-indigo-600 [&_a]:underline [&_a]:font-semibold">
                            {!! Illuminate\Mail\Markdown::parse($message->announcement->message) !!}
                        </div>

                        @if (isset($message->announcement->call_to_action_title, $message->announcement->call_to_action_url))
                            <div class="mt-6">
                                <x-filament::button tag="a" href="{{ $message->announcement->call_to_action_url }}" target="_blank">
                                    {{ $message->announcement->call_to_action_title }}
                                </x-filament::button>
                            </div>
                        @endif
                        
                        @guest
                            <div class="mt-8 border-t pt-6">
                                <p class="mb-4 text-sm">Sign in to view more announcements and access all features.</p>
                                <a href="{{ route('filament.admin.auth.login') }}" class="inline-flex items-center justify-center w-full md:w-auto px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 no-underline hover:no-underline !decoration-transparent" style="text-decoration: none !important;">
                                    <span class="text-white">Login Now</span>
                                </a>
                            </div>
                        @endguest
                        
                        @if(isset($showPlatformLink) && $showPlatformLink)
                            <div class="mt-8 border-t pt-6">
                                <a href="{{ $platformUrl }}" class="inline-flex items-center gap-x-2 text-indigo-600 hover:text-indigo-800 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2zm0-4h5V8h-5v2zM9 8H4v2h5V8z" clip-rule="evenodd" />
                                    </svg>
                                    View in Platform
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </x-layouts.simple-wrapper>
</x-layouts.app>