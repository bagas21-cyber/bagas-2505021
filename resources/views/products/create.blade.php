<x-app-layout>
    <x-slot name="title">Tambah Produk</x-slot><div class="mx-auto max-w-4xl"><div class="mb-8"><p class="mb-2 text-sm font-semibold text-teal-600">Data master</p><h1 class="font-display text-3xl font-extrabold tracking-tight">Tambah produk</h1><p class="mt-2 text-sm text-slate-500">Tambahkan produk baru ke katalog.</p></div><div class="card-surface p-6 sm:p-8"><form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">@include('products._form', ['buttonLabel' => 'Simpan produk'])</form></div></div>
</x-app-layout>
