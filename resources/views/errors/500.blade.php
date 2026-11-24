<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terjadi kesalahan · UMKMKu</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="flex min-h-screen items-center justify-center bg-[#102d2b] px-6 text-white">
    <main class="max-w-lg text-center">
        <p class="text-sm font-bold uppercase tracking-[0.3em] text-amber-300">Kesalahan 500</p>
        <h1 class="mt-5 font-display text-5xl font-extrabold tracking-tight">Terjadi kesalahan</h1>
        <p class="mt-4 text-teal-100/70">Maaf, terjadi gangguan pada aplikasi. Silakan coba lagi beberapa saat lagi.</p>
        <a href="{{ url('/') }}" class="mt-8 inline-flex rounded-xl bg-teal-400 px-5 py-3 text-sm font-bold text-[#102d2b] hover:bg-teal-300">Kembali ke beranda</a>
    </main>
</body>
</html>
