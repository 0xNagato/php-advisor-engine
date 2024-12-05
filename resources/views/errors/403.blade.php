<!-- resources/views/errors/403.blade.php -->

<x-layouts.app>
    <div class="min-h-screen">
        <div class="w-full pt-8 mb-2 text-3xl font-bold leading-5 tracking-tight text-center text-gray-950">
            PRIMA
        </div>
        <div class="text-2xl font-bold text-center dm-serif">
            Everybody Wins
        </div>

        <div class="flex items-center justify-center px-4 mt-8">
            <div class="w-full max-w-md">
                <x-filament::section>
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-900">
                            403 Error
                        </h2>

                        @auth
                            @php
                                $activeProfile = auth()
                                    ->user()
                                    ->roleProfiles()
                                    ->with('role')
                                    ->where('is_active', true)
                                    ->first();
                                $profiles = auth()
                                    ->user()
                                    ->roleProfiles()
                                    ->with('role')
                                    ->where('is_active', false)
                                    ->get();
                            @endphp

                            <p class="mt-2 text-base font-semibold text-black">
                                You do not have permission to access this page.
                            </p>

                            <div class="my-4">
                                <div
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-full border">
                                    Current Role: {{ formatRoleName($activeProfile?->role?->name ?? 'None') }}
                                </div>
                            </div>

                            @if ($profiles->count() > 0)
                                <p class="mt-2 text-sm text-gray-500">
                                    This can happen if you've recently switched roles in a different browser or in the
                                    mobile app.
                                    You can switch to an appropriate role below or return to the dashboard.
                                </p>

                                <div class="mt-6 space-y-3">
                                    @foreach ($profiles as $profile)
                                        <form action="{{ route('role.switch', $profile->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="w-full px-4 py-2 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                                                Switch to {{ formatRoleName($profile->role->name) }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-6">
                                <a href="{{ config('app.platform_url') }}"
                                    class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white transition bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-500">
                                    Return to Dashboard
                                </a>
                            </div>
                        @endauth
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-layouts.app>
