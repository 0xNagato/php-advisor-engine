<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Send SMS
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

@push('styles')
    <style>
        .fi-placeholder {
            --tw-border-opacity: 1;
            border: 1px solid rgb(226 232 240 / var(--tw-border-opacity));
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 0.5rem;
            background-color: rgb(249 250 251);
        }

        .fi-placeholder-content {
            line-height: 1.5;
        }

        .fi-fo-checkbox-list {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 0.25rem 0.5rem !important;
            row-gap: 0.75rem !important;
        }

        .fi-fo-checkbox-list-option {
            display: flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
        }

        .fi-fo-checkbox-list .fi-fo-checkbox-list-option-label {
            font-size: 11.5px !important;
            line-height: 1 !important;
            white-space: nowrap !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        @media (min-width: 768px) {
            .fi-fo-checkbox-list .fi-fo-checkbox-list-option-label {
                font-size: 14px !important;
                line-height: 1.25 !important;
            }
        }

        .fi-fo-checkbox-list input[type="checkbox"] {
            width: 16px !important;
            height: 16px !important;
            margin: 0 !important;
            flex-shrink: 0 !important;
        }

        /* Grid container */
        .fi-fo-checkbox-list {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 0.25rem 0.5rem !important;
            row-gap: 0.75rem !important;
        }

        /* Label container */
        .fi-fo-checkbox-list-option-label {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        /* Remove margin from checkbox */
        .fi-checkbox-input {
            margin: 0 !important;
            width: 16px !important;
            height: 16px !important;
        }

        /* Text container */
        .fi-fo-checkbox-list-option-label .grid {
            display: flex !important;
            align-items: center !important;
            font-size: 11.5px !important;
            line-height: 1 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Text span */
        .fi-fo-checkbox-list-option-label .grid span {
            margin-top: 1px !important;
            /* Fine-tune vertical alignment */
        }

        /* Desktop styles */
        @media (min-width: 768px) {
            .fi-fo-checkbox-list-option-label .grid {
                font-size: 14px !important;
                line-height: 1.25 !important;
            }
        }
    </style>
@endpush
