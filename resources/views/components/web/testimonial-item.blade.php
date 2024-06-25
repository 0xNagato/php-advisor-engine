@props(['testimonial'])
<div {{ $attributes->class(['text-center rounded border']) }}>
    <div class="flex flex-col h-full">
        <div class="text-left font-light flex-grow p-6">
            {{ $testimonial['quote'] }}
        </div>

        <div class="bg-gray-100 border-t p-6">
            <div class="font-normal">
                {{ $testimonial['author'] }}
            </div>

            <p class="text-sm text-gray-500 font-semibold">
                {{ $testimonial['venue'] }}
            </p>
        </div>
    </div>
</div>
