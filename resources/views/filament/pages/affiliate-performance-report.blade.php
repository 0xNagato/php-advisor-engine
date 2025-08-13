<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="col-span-1 lg:col-span-4">
                @livewire(\App\Livewire\AffiliateMonthlyTrendsChart::class, [
                    'startMonth' => $this->data['startMonth'] ?? null,
                    'numberOfMonths' => $this->data['numberOfMonths'] ?? null,
                    'region' => $this->data['region'] ?? null,
                    'search' => $this->data['search'] ?? null,
                ])
            </div>
            <div class="col-span-1 lg:col-span-4">
                @livewire(\App\Livewire\TopAffiliatesByBookingsChart::class, [
                    'startMonth' => $this->data['startMonth'] ?? null,
                    'numberOfMonths' => $this->data['numberOfMonths'] ?? null,
                    'region' => $this->data['region'] ?? null,
                    'search' => $this->data['search'] ?? null,
                ])
            </div>
            <div class="col-span-1 lg:col-span-4">
                @livewire(\App\Livewire\TopAffiliatesByEarningsChart::class, [
                    'startMonth' => $this->data['startMonth'] ?? null,
                    'numberOfMonths' => $this->data['numberOfMonths'] ?? null,
                    'region' => $this->data['region'] ?? null,
                    'search' => $this->data['search'] ?? null,
                ])
            </div>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
