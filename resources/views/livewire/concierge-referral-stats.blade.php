<x-filament-widgets::widget>
    <div class="sm:mt-0">
        <dl class="grid divide-gray-200 overflow-hidden rounded-lg bg-white shadow grid-cols-2 divide-x divide-y-0">
            <!-- Earnings -->
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-xs font-semibold text-slate-900">Earnings</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-xl sm:text-2xl font-semibold text-indigo-600">
                        {{ money($stats['earnings']) }}
                    </div>
                </dd>
            </div>

            <!-- Referrals -->
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-xs font-semibold text-slate-900">Bookings</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-xl sm:text-2xl font-semibold text-indigo-600">
                        {{ $stats['referrals'] }}
                    </div>
                </dd>
            </div>
        </dl>
    </div>
</x-filament-widgets::widget>
