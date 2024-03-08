<x-filament-widgets::widget>
    <div class="sm:mt-0">
        <dl class="mt-5 grid divide-gray-200 overflow-hidden rounded-lg bg-white shadow grid-cols-3 divide-x divide-y-0">
            <!-- Earnings -->
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-xs font-semibold text-slate-900">Earnings</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex flex-col items-baseline text-xl sm:text-2xl font-semibold text-indigo-600">
                        <div>
                            {{ $stats->formatted['platform_earnings'] }}
                        </div>
                        <div
                            @class(['flex items-center', 'text-[11px]', 'text-green-600' => $stats->difference['platform_earnings_up'], 'text-red-600' => !$stats->difference['platform_earnings_up']])>
                            <div>{{ $stats->formatted['difference']['platform_earnings'] }}</div>

                            @if ($stats->difference['platform_earnings_up'])
                                <x-filament::icon icon="heroicon-o-arrow-up-right" class="w-4 h-4"/>
                            @else
                                <x-filament::icon icon="heroicon-o-arrow-down-right" class="w-4 h-4"/>
                            @endif
                        </div>
                    </div>
                </dd>
            </div>

            <!-- Charity -->
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-xs font-semibold text-slate-900">Charity</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex flex-col items-baseline text-xl sm:text-2xl font-semibold text-indigo-600">
                        <div>
                            {{ $stats->formatted['charity_earnings'] }}
                        </div>
                        <div
                            @class(['flex items-center', 'text-[11px]', 'text-green-600' => $stats->difference['charity_earnings_up'], 'text-red-600' => !$stats->difference['charity_earnings_up']])>
                            <div>{{ $stats->formatted['difference']['charity_earnings'] }}</div>

                            @if ($stats->difference['charity_earnings_up'])
                                <x-filament::icon icon="heroicon-o-arrow-up-right" class="w-4 h-4"/>
                            @else
                                <x-filament::icon icon="heroicon-o-arrow-down-right" class="w-4 h-4"/>
                            @endif
                        </div>
                    </div>
                </dd>
            </div>

            <!-- Bookings -->
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-xs font-semibold text-slate-900">Bookings</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex flex-col items-baseline text-xl sm:text-2xl font-semibold text-indigo-600">
                        {{ $stats->formatted['number_of_bookings'] }}
                        <div
                            @class(['flex items-center', 'text-[11px]', 'text-green-600' => $stats->difference['number_of_bookings_up'], 'text-red-600' => !$stats->difference['number_of_bookings_up']])>
                            <div>{{ $stats->difference['number_of_bookings'] }}</div>

                            @if ($stats->difference['number_of_bookings_up'])
                                <x-filament::icon icon="heroicon-o-arrow-up-right" class="w-4 h-4"/>
                            @else
                                <x-filament::icon icon="heroicon-o-arrow-down-right" class="w-4 h-4"/>
                            @endif
                        </div>
                    </div>
                </dd>
            </div>
        </dl>
    </div>
</x-filament-widgets::widget>
