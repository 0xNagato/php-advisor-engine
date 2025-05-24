<div class="flex flex-col items-center justify-center p-4">
    @if ($getRecord()->qr_code_path)
        <img src="{{ asset('storage/' . $getRecord()->qr_code_path) }}" 
             alt="QR Code for {{ $getRecord()->url_key }}" 
             class="max-w-xs max-h-64 mb-4">
        
        <div class="flex space-x-2">
            <a href="{{ asset('storage/' . $getRecord()->qr_code_path) }}" 
               target="_blank"
               class="px-4 py-2 text-white bg-primary-600 rounded hover:bg-primary-500 transition">
                View Full Size
            </a>
            
            <a href="{{ $getRecord()->shortUrl }}" 
               target="_blank"
               class="px-4 py-2 text-white bg-gray-600 rounded hover:bg-gray-500 transition">
                Test URL
            </a>
        </div>
    @else
        <div class="text-gray-500">No QR code image available</div>
    @endif
</div>