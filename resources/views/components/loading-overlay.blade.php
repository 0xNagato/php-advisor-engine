<div wire:loading class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-85">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div
                class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6">
            <div>
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-indigo-100 rounded-full">
                    <x-filament::loading-indicator class="w-6 h-6 text-indigo-600"/>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg font-semibold text-white">
                        Loading Data...
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>
