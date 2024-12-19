<div>
    @if ($label)
        <label class="block mb-2 text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif
    <div class="flex items-center gap-4">
        <div class="flex-1">
            <label
                class="flex items-center justify-center w-full h-32 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-indigo-600">
                <div class="space-y-1 text-center">
                    @if ($file)
                        @if (str_starts_with($file->getMimeType(), 'image/'))
                            <div class="flex items-center justify-center w-full h-28">
                                <img src="{{ $file->temporaryUrl() }}" alt="Preview"
                                    class="object-contain max-w-full max-h-full">
                            </div>
                        @else
                            <div class="text-sm text-gray-600">
                                {{ $file->getClientOriginalName() }}
                            </div>
                        @endif
                    @else
                        <svg class="w-8 h-8 mx-auto text-gray-400" stroke="currentColor" fill="none"
                            viewBox="0 0 48 48" aria-hidden="true">
                            <path
                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="text-sm text-gray-600">
                            Click to upload or drag and drop
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG up to 2MB</p>
                    @endif
                </div>
                <input type="file" name="{{ $name }}"
                    @if ($model) wire:model="{{ $model }}" @endif class="sr-only"
                    accept="image/*">
            </label>
        </div>
        @if ($showDelete && $file)
            <button type="button" {{ $attributes->get('wire:click') }}
                class="p-2 text-gray-500 transition rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        @endif
    </div>
    @if ($error)
        <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
