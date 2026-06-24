@props(['title' => 'IELTS Reading Test', 'scrollable' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['h-full' => ! $scrollable])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .reading-pane { height: calc(100vh - 7.5rem); }
        @media (max-width: 1023px) {
            .reading-pane { height: calc(100vh - 11rem); }
        }
    </style>
</head>
<body @class([
    'bg-[#eef1f4] font-sans text-neutral-900 antialiased',
    'min-h-screen overflow-y-auto' => $scrollable,
    'h-screen overflow-hidden' => ! $scrollable,
])>
    {{ $slot }}
</body>
</html>
