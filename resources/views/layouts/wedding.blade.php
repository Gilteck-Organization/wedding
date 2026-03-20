<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'Wedding') }}</title>

        {{-- Wedding fonts --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=montserrat:400,500,600|playfair-display:400,500,600,700|cormorant-garamond:400,500,600,700|great-vibes:400"
            rel="stylesheet"
        />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-wedding-cream text-wedding-ink font-sans">
        <div class="min-h-screen flex flex-col">
            @isset($header)
                <header class="w-full bg-transparent">
                    {{ $header }}
                </header>
            @endisset

            <main class="flex-1">
                @if (session('success'))
                    <div class="mx-auto max-w-5xl px-4 sm:px-6 pt-4">
                        <div class="border border-wedding-primary/30 bg-wedding-ivory p-4 text-wedding-ink shadow-sm">
                            <p class="font-medium text-wedding-primary">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif
                @yield('content')
            </main>

            @if (! trim($__env->yieldContent('hideFooter')))
                <footer class="w-full py-10">
                    <div class="mx-auto max-w-5xl px-4 sm:px-6 text-center text-sm text-wedding-muted">
                        {{ config('app.name', 'Wedding') }} RSVP
                    </div>
                </footer>
            @endif
        </div>
    </body>
</html>

