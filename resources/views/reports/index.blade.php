<x-app-layout>
    <x-slot name="title">Laporan</x-slot>
    <div class="mx-auto max-w-[1440px]">
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="mb-2 text-sm font-semibold text-teal-600">Analitik toko</p><h1 class="font-display text-3xl font-extrabold tracking-tight">Laporan {{ ucfirst($type) }}</h1><p class="mt-2 text-sm text-slate-500">Pantau data {{ $type }} dalam periode terpilih.</p></div><div class="flex gap-2"><a href="{{ route('reports.export', array_merge(request()->query(), ['type' => $type, 'format' => 'csv'])) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">CSV</a><a href="{{ route('reports.export', array_merge(request()->query(), ['type' => $type, 'format' => 'pdf'])) }}" class="rounded-xl bg-teal-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-teal-500/20 hover:bg-teal-600">PDF</a></div></div>
        <div class="card-surface mb-6 p-5"><form method="GET" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]"><input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-xl border-slate-200 py-2.5 text-sm"><input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-xl border-slate-200 py-2.5 text-sm"><button class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700">Tampilkan</button></form></div>
        @if($type === 'sales')
            <div class="mb-6 grid gap-4 sm:grid-cols-3"><div class="card-surface p-5"><p class="text-sm text-slate-500">Total penjualan</p><p class="mt-2 text-2xl font-extrabold">Rp {{ number_format($metrics['revenue'], 0, ',', '.') }}</p></div><div class="card-surface p-5"><p class="text-sm text-slate-500">Transaksi</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($metrics['transactions'], 0, ',', '.') }}</p></div><div class="card-surface p-5"><p class="text-sm text-slate-500">Produk terjual</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($metrics['quantity'], 0, ',', '.') }}</p></div></div>
            @include('reports.partials.sales')
        @elseif($type === 'stock')
            <div class="mb-6 grid gap-4 sm:grid-cols-3">@foreach(['in' => 'Stok masuk', 'out' => 'Stok keluar', 'adjustment' => 'Penyesuaian'] as $key => $label)<div class="card-surface p-5"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($summary[$key], 0, ',', '.') }}</p></div>@endforeach</div>
            @include('reports.partials.stock')
        @else
            <div class="mb-6 grid gap-4 sm:grid-cols-3"><div class="card-surface p-5"><p class="text-sm text-slate-500">Penjualan</p><p class="mt-2 text-2xl font-extrabold">Rp {{ number_format($totals['revenue'], 0, ',', '.') }}</p></div><div class="card-surface p-5"><p class="text-sm text-slate-500">Modal</p><p class="mt-2 text-2xl font-extrabold">Rp {{ number_format($totals['cost'], 0, ',', '.') }}</p></div><div class="card-surface p-5"><p class="text-sm text-slate-500">Laba kotor</p><p class="mt-2 text-2xl font-extrabold text-emerald-600">Rp {{ number_format($totals['profit'], 0, ',', '.') }}</p></div></div>
            @include('reports.partials.profit')
        @endif
    </div>
</x-app-layout>
