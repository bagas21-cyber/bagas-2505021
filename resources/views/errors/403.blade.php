<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses ditolak · UMKMKu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-6 text-slate-900">
    <main class="card-surface w-full max-w-lg p-8 text-center">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-rose-500">403</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold">Akses ditolak</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500">Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mt-6 inline-flex rounded-xl bg-teal-500 px-5 py-3 text-sm font-bold text-white hover:bg-teal-600">Kembali</a>
    </main>
</body>
</html>
