<x-app-layout>
    <x-slot name="title">Edit Produk</x-slot><div class="mx-auto max-w-4xl"><div class="mb-8"><p class="mb-2 text-sm font-semibold text-teal-600">Data master</p><h1 class="font-display text-3xl font-extrabold tracking-tight">Edit produk</h1><p class="mt-2 text-sm text-slate-500">Perbarui informasi katalog produk.</p></div><div class="card-surface p-6 sm:p-8"><form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">@method('PUT')@include('products._form', ['buttonLabel' => 'Simpan perubahan'])</form></div></div>
</x-app-layout>
