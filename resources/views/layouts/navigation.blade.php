<aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-[#102d2b] px-5 py-7 text-white shadow-2xl transition-transform lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="mb-10 flex items-center justify-between px-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-400 text-xl font-extrabold text-[#102d2b]">U</span><span class="font-display text-xl font-extrabold tracking-tight">UMKM<span class="text-teal-300">Ku</span></span></a>
        <button @click="sidebarOpen = false" class="rounded-lg p-2 text-teal-100/60 hover:bg-white/10 lg:hidden" aria-label="Tutup menu">×</button>
    </div>
    <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-teal-100/45">Menu Utama</p>
    <nav class="space-y-1">
        @php
            $menus = [
                ['dashboard', 'Dashboard', '⌂'], ['products.index', 'Produk', '▦'], ['categories.index', 'Kategori', '◈'],
                ['suppliers.index', 'Supplier', '♙'], ['stock-ins.index', 'Stok Masuk', '↘'], ['sales.index', 'Penjualan', '↗'],
            ];
        @endphp
        @foreach ($menus as [$routeName, $label, $icon])
            <a href="{{ route($routeName) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ request()->routeIs($routeName) || ($routeName === 'categories.index' && request()->routeIs('categories.*')) || ($routeName === 'suppliers.index' && request()->routeIs('suppliers.*')) ? 'bg-teal-400 text-[#102d2b] shadow-lg shadow-teal-950/20' : 'text-teal-50/70 hover:bg-white/10 hover:text-white' }}"><span class="w-6 text-center text-lg">{{ $icon }}</span>{{ $label }}</a>
        @endforeach
    </nav>
    <p class="mb-3 mt-9 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-teal-100/45">Lainnya</p>
    <nav class="space-y-1">
        @foreach ([['reports.index', 'Laporan', '▤'], ['settings.index', 'Pengaturan', '⚙']] as [$routeName, $label, $icon])
            <a href="{{ route($routeName) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-teal-50/70 transition hover:bg-white/10 hover:text-white"><span class="w-6 text-center text-lg">{{ $icon }}</span>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="mt-auto rounded-2xl bg-white/10 p-4"><p class="text-xs font-bold text-teal-100">Butuh bantuan?</p><p class="mt-1 text-xs leading-relaxed text-teal-100/60">Pelajari cara mengelola toko lebih cepat.</p><a href="#" class="mt-3 inline-block text-xs font-bold text-teal-300">Lihat panduan →</a></div>
    <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-teal-50/70 hover:bg-rose-400/10 hover:text-rose-200"><span class="w-6 text-center">↪</span>Keluar</button></form>
</aside>
