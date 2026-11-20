@if (session('success') || session('error'))
    <div x-data="{ visible: true }" x-show="visible" x-transition x-init="setTimeout(() => visible = false, 4500)" class="fixed right-5 top-24 z-50 w-[calc(100%-2.5rem)] max-w-md" role="status">
        <div class="flex items-start gap-3 rounded-2xl border p-4 shadow-xl {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' }}">
            <span class="mt-0.5 text-lg">{{ session('success') ? '✓' : '!' }}</span>
            <p class="flex-1 text-sm font-semibold">{{ session('success') ?? session('error') }}</p>
            <button type="button" @click="visible = false" class="text-lg leading-none opacity-60 hover:opacity-100" aria-label="Tutup notifikasi">&times;</button>
        </div>
    </div>
@endif
