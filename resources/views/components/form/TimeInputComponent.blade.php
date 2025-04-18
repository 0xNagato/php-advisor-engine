<!-- resources/views/components/time-input.blade.php -->
<div class="w-1/2">
    <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    <div class="relative">
        <input type="time"
               id="{{ $id }}"
               wire:model="{{ $model }}"
               {{ $attributes->merge(['class' => 'bg-gray-50 border leading-none border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full px-2 p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500']) }}
               min="00:00"
               max="24:00"
               step="1800"
        />
    </div>
    @error($errorKey)
        <span class="text-red-500 text-xs">{{ $message }}</span>
    @enderror
</div>