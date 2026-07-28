<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' · ' : '' }}{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Vendor CSS (CDN) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        <!-- App assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [x-cloak] { display: none !important; }
            /* Sidebar mode kecil (hanya desktop): sembunyikan teks, sisakan ikon */
            @media (min-width: 1024px) {
                .sidebar-collapsed .nav-label,
                .sidebar-collapsed .nav-section,
                .sidebar-collapsed .nav-brand-text,
                .sidebar-collapsed .nav-footer-text,
                .sidebar-collapsed .nav-chevron,
                .sidebar-collapsed .nav-report-sub,
                .sidebar-collapsed .nav-logout { display: none !important; }
                .sidebar-collapsed .nav-item,
                .sidebar-collapsed .nav-brand,
                .sidebar-collapsed .nav-footer-inner { justify-content: center; }
            }
        </style>
        @stack('styles')
    </head>
    <body class="h-full font-sans text-slate-800 antialiased">
        <div x-data="adminShell()" class="min-h-full bg-white">

            <!-- Mobile backdrop -->
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
                x-transition.opacity
                class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

            <!-- Sidebar -->
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white text-slate-600 transition-all duration-300 lg:translate-x-0"
                :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': ! sidebarOpen, 'lg:w-[74px] sidebar-collapsed': sidebarCollapsed }">
                @include('layouts.sidebar')
            </aside>

            <!-- Main column -->
            <div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:pl-[74px]' : 'lg:pl-64'">
                <!-- Topbar -->
                <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                        <button @click="sidebarCollapsed = ! sidebarCollapsed" title="Perkecil / perbesar sidebar"
                            class="hidden rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:inline-flex">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A1.5 1.5 0 0 1 5.25 3.75h13.5a1.5 1.5 0 0 1 1.5 1.5v13.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V5.25Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.75v16.5"/>
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            <h1 class="truncate text-base font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                            @isset($subtitle)
                                <p class="truncate text-xs text-slate-400">{{ $subtitle }}</p>
                            @endisset
                        </div>

                        <!-- Search (desktop) -->
                        <div class="relative hidden md:block">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            </span>
                            <input type="text" placeholder="Cari..." class="w-56 rounded-xl border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                        </div>

                        <!-- Notifications -->
                        <button class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                        </button>

                        <!-- User dropdown -->
                        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                            <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                                class="flex items-center gap-2 rounded-xl p-1 pr-2 transition hover:bg-slate-100"
                                :class="open && 'bg-slate-100'">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden text-left sm:block">
                                    <span class="block text-sm font-semibold leading-tight text-slate-900">{{ Auth::user()->name }}</span>
                                    <span class="block text-xs capitalize leading-tight text-slate-400">{{ Auth::user()->role ?? 'staff' }}</span>
                                </span>
                                <svg class="hidden h-4 w-4 text-slate-400 transition-transform sm:block" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="open" x-cloak
                                @click.outside="open = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10">
                                <div class="px-3 py-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                                    <p class="truncate text-xs text-slate-400">{{ Auth::user()->email }}</p>
                                </div>
                                <div class="my-1 h-px bg-slate-100"></div>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                    Profil Saya
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page content -->
                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('success') || session('status') || session('error'))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)" x-transition
                            class="mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm
                                {{ session('error') ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <span class="flex-1">{{ session('success') ?? session('status') ?? session('error') }}</span>
                            <button @click="show = false" class="text-current/60 hover:text-current">&times;</button>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Vendor JS (CDN) -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <script>
            function adminShell() {
                return {
                    sidebarOpen: false,
                    sidebarCollapsed: false,
                    init() {
                        this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === '1';
                        this.$watch('sidebarCollapsed', v => localStorage.setItem('sidebarCollapsed', v ? '1' : '0'));
                    },
                };
            }
        </script>
        @stack('scripts')
    </body>
</html>
