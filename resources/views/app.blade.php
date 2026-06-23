<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #fafafa;
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        {{-- Geist + Geist Mono are self-hosted via @fontsource (imported in app.ts). --}}

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            @php($seo = $page['props']['seo'] ?? null)
            <title>{{ $seo['title'] ?? config('app.name', 'Laravel') }}</title>
            @if ($seo)
                <meta name="description" content="{{ $seo['description'] }}">
                <link rel="canonical" href="{{ $seo['url'] }}">

                <meta property="og:type" content="website">
                <meta property="og:site_name" content="Dwellow">
                <meta property="og:title" content="{{ $seo['title'] }}">
                <meta property="og:description" content="{{ $seo['description'] }}">
                <meta property="og:url" content="{{ $seo['url'] }}">
                <meta property="og:image" content="{{ $seo['image'] }}">

                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:title" content="{{ $seo['title'] }}">
                <meta name="twitter:description" content="{{ $seo['description'] }}">
                <meta name="twitter:image" content="{{ $seo['image'] }}">

                <script type="application/ld+json">
                    {!! json_encode([
                        '@context' => 'https://schema.org',
                        '@type' => 'SoftwareApplication',
                        'name' => 'Dwellow',
                        'applicationCategory' => 'BusinessApplication',
                        'operatingSystem' => 'Web',
                        'description' => $seo['description'],
                        'url' => $seo['url'],
                        'image' => $seo['image'],
                        'offers' => [
                            '@type' => 'Offer',
                            'price' => '0',
                            'priceCurrency' => 'USD',
                        ],
                        'publisher' => [
                            '@type' => 'Organization',
                            'name' => 'Dwellow',
                            'url' => $seo['url'],
                            'logo' => $seo['image'],
                        ],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
                </script>
            @endif
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
