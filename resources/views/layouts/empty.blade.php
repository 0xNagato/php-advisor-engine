<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Prima') }}</title>

    <!-- Scripts -->
    @stack('scripts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>

    <link
        href="https://db.onlinewebfonts.com/c/5b381abe79163202b03f53ed0eab3065?family=Sanomat+Web+Regular+Regular"
        rel="stylesheet">

    <link href="https://db.onlinewebfonts.com/c/e48e18fc2422049eaba9fc43548b664b?family=Melete+Bold" rel="stylesheet"
          type="text/css"/>
    <!-- Styles -->
    @livewireStyles
</head>

<body>
{{ $slot }}
@livewireScripts
</body>

</html>
