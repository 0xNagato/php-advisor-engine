<!DOCTYPE html>
<html>

<head>
    <title>{{ $title ?? 'PRIMA' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@100..900&display=swap"
        rel="stylesheet">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-9GTWLVMQJW"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-9GTWLVMQJW');
    </script>
    @filamentStyles
    @vite('resources/css/web.css')
</head>

<body x-data="modalHandler">

    {{-- New Prima Modal --}}
    <div class="h-0">
        <x-prima-modal id="prima-contact" maxWidth="2xl" heading="Talk to PRIMA">
            <livewire:talk-to-prima />
        </x-prima-modal>
    </div>

    <x-layouts.web-header />

    {{ $slot }}

    <x-layouts.web-footer />
    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>
