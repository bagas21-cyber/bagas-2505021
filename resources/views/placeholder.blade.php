<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>
    <div class="mx-auto max-w-4xl"><div class="card-surface flex min-h-[420px] flex-col items-center justify-center p-8 text-center"><div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50 text-3xl text-teal-600">◌</div><h1 class="font-display text-2xl font-extrabold">{{ $title }}</h1><p class="mt-2 max-w-md text-sm leading-6 text-slate-500">Halaman {{ strtolower($title) }} sedang disiapkan. Gunakan menu Dashboard untuk kembali melihat ringkasan toko.</p><a href="{{ route('dashboard') }}" class="mt-6 rounded-xl bg-teal-500 px-4 py-3 text-sm font-bold text-white hover:bg-teal-600">Kembali ke dashboard</a></div></div>
</x-app-layout>
