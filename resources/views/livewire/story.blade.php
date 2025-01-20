<div>
    <livewire:comic-strip :pages="['/images/comic/1.webp', '/images/comic/2.webp', '/images/comic/3.webp', '/images/comic/4.webp']" />

    <div
        class="fixed inset-x-0 bottom-0 z-50 flex items-center justify-center gap-4 p-3 text-xs font-medium text-indigo-600 shadow-lg bg-white/95 backdrop-blur-md whitespace-nowrap">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            <span>Swipe right to navigate</span>
        </div>

        <div class="w-px h-4 bg-indigo-200"></div>

        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span>Double tap to zoom</span>
        </div>
    </div>
</div>
