@props(['heading' => null, 'subtitle' => null, 'maxWidth' => '440px'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center bg-violet-600 px-4 py-10">
            <div class="w-full bg-white rounded-2xl shadow-sm px-6 py-8 sm:px-10 sm:py-10" style="max-width: {{ $maxWidth }}">
                @if ($heading)
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-semibold text-gray-900">{{ $heading }}</h1>
                        @if ($subtitle)
                            <p class="mt-1.5 text-sm text-gray-500">{{ $subtitle }}</p>
                        @endif
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
