<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Dashboard' }} · UMKMKu</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-body text-slate-900 antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>
            @include('layouts.navigation')
            <div class="lg:pl-72">
                <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200/80 bg-slate-50/90 px-5 backdrop-blur lg:px-10">
                    <button @click="sidebarOpen = true" class="rounded-xl p-2 text-slate-500 hover:bg-white lg:hidden" aria-label="Buka menu"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                    <div class="hidden text-sm text-slate-500 sm:block">{{ $todayLabel ?? now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</div>
                    <div class="ml-auto flex items-center gap-3">
                        <button class="relative rounded-xl p-2.5 text-slate-500 hover:bg-white" aria-label="Notifikasi"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 01-6 0v-1"/></svg><span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-slate-50"></span></button>
                        <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>
                        <div class="flex items-center gap-3"><div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 font-bold text-teal-700">{{ substr(Auth::user()->name, 0, 1) }}</div><div class="hidden leading-tight sm:block"><p class="text-sm font-bold text-slate-800">{{ Auth::user()->name }}</p><p class="text-xs text-slate-500">{{ ucfirst(Auth::user()->role ?? 'Admin') }}</p></div></div>
                    </div>
                </header>
                <main class="px-5 py-7 lg:px-10 lg:py-9">{{ $slot }}</main>
            </div>
            <x-flash />
        </div>
        @stack('scripts')
    </body>
</html>
