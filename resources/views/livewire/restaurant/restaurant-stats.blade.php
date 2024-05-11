<x-filament-widgets::widget>
    <div class="sm:mt-0">
        <dl class="grid divide-gray-200 overflow-hidden rounded-lg bg-white shadow grid-cols-2 divide-x divide-y-0">
            <!-- Bookings -->
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-xs font-semibold text-slate-900">Bookings</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex flex-col items-baseline text-2xl font-semibold text-indigo-600">
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

            <!-- Earnings -->
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-xs font-semibold text-slate-900">Earnings</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex flex-col items-baseline text-2xl font-semibold text-indigo-600">
                        <div>
                            {{ $stats->formatted['restaurant_earnings'] }}
                        </div>
                        <div
                            @class(['flex items-center', 'text-[11px]', 'text-green-600' => $stats->difference['restaurant_earnings_up'], 'text-red-600' => !$stats->difference['restaurant_earnings_up']])>
                            <div>{{ money($stats->difference['restaurant_earnings'], $currency) }}</div>

                            @if ($stats->difference['restaurant_earnings_up'])
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
