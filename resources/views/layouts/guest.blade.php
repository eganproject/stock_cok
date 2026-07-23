<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} &middot; Masuk</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">
            <!-- Brand panel -->
            <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-slate-900 p-12 text-white">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-800 via-slate-900 to-black"></div>
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute bottom-0 -left-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20 backdrop-blur">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5M12 12v9M3.75 7.5v9L12 21" />
                        </svg>
                    </div>
                    <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                </div>

                <div class="relative">
                    <h1 class="text-4xl font-bold leading-tight tracking-tight">
                        Pantau inventory<br>setiap gudang<br>dalam satu dashboard.
                    </h1>
                    <p class="mt-5 max-w-md text-slate-300">
                        Kelola stok, lacak pergerakan barang, dan ambil keputusan lebih cepat dengan data real-time dari seluruh cabang.
                    </p>

                    <ul class="mt-8 space-y-3 text-sm text-slate-200">
                        @foreach (['Data stok real-time via API', 'Multi-gudang & multi-cabang', 'Laporan interaktif & filter cepat'] as $feature)
                            <li class="flex items-center gap-3">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative text-xs text-slate-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Seluruh hak cipta dilindungi.
                </div>
            </div>

            <!-- Form panel -->
            <div class="flex items-center justify-center bg-slate-50 px-6 py-12 sm:px-12">
                <div class="w-full max-w-md">
                    <!-- Mobile logo -->
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5M12 12v9M3.75 7.5v9L12 21" />
                            </svg>
                        </div>
                        <span class="text-lg font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
