<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Send SMS
            </x-filament::button>
        </div>
    </form>

    {{-- @if (config('app.env') === 'local') --}}
    <div class="h-64 p-4 mt-4 overflow-y-auto font-mono text-xs text-white bg-gray-800 rounded-lg">
        <div class="mb-2 text-yellow-400">
            Selected Regions: {{ implode(', ', $this->data['regions'] ?? []) }}
        </div>
        <div class="mb-2 text-yellow-400">
            Selected Recipients: {{ implode(', ', $this->data['recipients'] ?? []) }}
        </div>
        <div class="space-y-1">
            @foreach ($this->getSelectedRecipients() as $recipient)
                <div>
                    <span class="text-purple-400">{{ $recipient->role_type }}</span>
                    <span class="text-blue-400">{{ $recipient->first_name }} {{ $recipient->last_name }}</span>
                    <span class="text-gray-400">{{ $recipient->phone }}</span>
                    @if (property_exists($recipient, 'notification_regions'))
                        <span class="text-green-400">(Regions:
                            {{ json_encode($recipient->notification_regions) }})</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    {{-- @endif --}}

    <x-filament-actions::modals />
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
