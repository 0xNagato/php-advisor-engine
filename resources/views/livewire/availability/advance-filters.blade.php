<x-filament-widgets::widget class="-mt-4 -mb-3">
    <div class="advance-filters-container flex justify-end">
        {{ $this->form }}
    </div>

    <style>
        .advance-filters-container .gap-6 {
            gap: 0.2rem !important;
        }

        /* Fix: let's keep the toggle standard size but reduce the font only */
        .toggle-sm .text-xs {
            font-size: 0.75rem !important;
        }
        
        /* Make sure the form takes appropriate width */
        .advance-filters-container .inline-form {
            width: auto !important;
            min-width: 120px;
            justify-content: flex-end;
        }
    </style>
</x-filament-widgets::widget>
