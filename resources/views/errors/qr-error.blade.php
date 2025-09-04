<x-layouts.app>
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="w-full text-3xl font-black text-center">PRIMA</div>
                <div class="p-2 text-2xl font-black text-center dm-serif">
                    Everybody Wins<span class="font-sans font-normal">™</span>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="w-20 h-20 mx-auto mb-6 bg-indigo-100 rounded-full flex items-center justify-center">
                    <x-heroicon-o-wrench-screwdriver class="w-12 h-12 text-indigo-600" />
                </div>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">
                    {{ $message ?? 'This QR code is experiencing a technical issue.' }}
                </h2>
                <p class="text-gray-600">
                    {{ $support_message ?? 'Please contact support for assistance.' }}
                </p>
                
                @if(config('app.support_email'))
                    <p class="mt-4 text-sm text-gray-500">Support: {{ config('app.support_email') }}</p>
                @endif
            </div>
            
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-full font-semibold text-white hover:bg-indigo-700 transition-colors">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>