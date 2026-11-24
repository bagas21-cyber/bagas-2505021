<x-app-layout>
    <x-slot name="title">Pengaturan</x-slot>
    <div class="mx-auto max-w-6xl">
        <div class="mb-8"><p class="mb-2 text-sm font-semibold text-teal-600">Administrasi</p><h1 class="font-display text-3xl font-extrabold tracking-tight">Pengaturan</h1><p class="mt-2 text-sm text-slate-500">Kelola profil, informasi toko, pengguna, dan preferensi aplikasi.</p></div>
        <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
            <nav class="card-surface h-fit p-2">
                @foreach ([['profile', 'Profil saya'], ['store', 'Informasi toko'], ['preferences', 'Preferensi'], ['users', 'Pengguna']] as [$key, $label])
                    <a href="{{ route('settings.index', ['tab' => $key]) }}" class="block rounded-xl px-4 py-3 text-sm font-bold {{ $tab === $key ? 'bg-teal-50 text-teal-700' : 'text-slate-500 hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <section class="card-surface p-6 sm:p-8">
                @if ($tab === 'profile')
                    <h2 class="font-display text-xl font-extrabold">Profil saya</h2><p class="mt-1 text-sm text-slate-500">Perbarui nama dan alamat email akun Anda.</p>
                    <form method="POST" action="{{ route('settings.profile.update') }}" class="mt-6 space-y-4">@csrf @method('PATCH')
                        <div><x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
                        <div><x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required /><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
                        <x-primary-button>Simpan profil</x-primary-button>
                    </form>
                @elseif ($tab === 'store')
                    <h2 class="font-display text-xl font-extrabold">Informasi toko</h2><p class="mt-1 text-sm text-slate-500">Informasi ini dapat digunakan pada struk dan laporan.</p>
                    <form method="POST" action="{{ route('settings.store.update') }}" class="mt-6 space-y-4">@csrf @method('PUT')
                        @foreach ([['store_name', 'Nama toko', 'text'], ['store_address', 'Alamat toko', 'text'], ['store_phone', 'Nomor telepon', 'text']] as [$key, $label, $type])<div><x-input-label for="{{ $key }}" :value="$label" /><x-text-input id="{{ $key }}" name="{{ $key }}" type="{{ $type }}" class="mt-1 block w-full" :value="old($key, $settings[$key] ?? '')" /><x-input-error :messages="$errors->get($key)" class="mt-2" /></div>@endforeach
                        <x-primary-button>Simpan informasi toko</x-primary-button>
                    </form>
                @elseif ($tab === 'preferences')
                    <h2 class="font-display text-xl font-extrabold">Preferensi</h2><p class="mt-1 text-sm text-slate-500">Atur format mata uang, zona waktu, dan footer struk.</p>
                    <form method="POST" action="{{ route('settings.preferences.update') }}" class="mt-6 space-y-4">@csrf @method('PUT')
                        <div><x-input-label for="currency" value="Mata uang" /><x-text-input id="currency" name="currency" class="mt-1 block w-full" :value="old('currency', $settings['currency'] ?? 'IDR')" required /><x-input-error :messages="$errors->get('currency')" class="mt-2" /></div>
                        <div><x-input-label for="timezone" value="Zona waktu" /><select id="timezone" name="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"><option value="Asia/Jakarta" @selected(old('timezone', $settings['timezone'] ?? 'Asia/Jakarta') === 'Asia/Jakarta')>Asia/Jakarta</option><option value="Asia/Makassar" @selected(old('timezone', $settings['timezone'] ?? '') === 'Asia/Makassar')>Asia/Makassar</option><option value="Asia/Jayapura" @selected(old('timezone', $settings['timezone'] ?? '') === 'Asia/Jayapura')>Asia/Jayapura</option></select><x-input-error :messages="$errors->get('timezone')" class="mt-2" /></div>
                        <div><x-input-label for="receipt_footer" value="Footer struk" /><textarea id="receipt_footer" name="receipt_footer" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('receipt_footer', $settings['receipt_footer'] ?? '') }}</textarea><x-input-error :messages="$errors->get('receipt_footer')" class="mt-2" /></div>
                        <x-primary-button>Simpan preferensi</x-primary-button>
                    </form>
                @else
                    <div class="flex items-start justify-between gap-4"><div><h2 class="font-display text-xl font-extrabold">Pengguna</h2><p class="mt-1 text-sm text-slate-500">Kelola akun admin dan kasir.</p></div></div>
                    <form method="POST" action="{{ route('settings.users.store') }}" class="mt-6 grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2">@csrf
                        <input name="name" placeholder="Nama" value="{{ old('name') }}" class="rounded-xl border-slate-200 text-sm" required><input name="email" type="email" placeholder="Email" value="{{ old('email') }}" class="rounded-xl border-slate-200 text-sm" required><select name="role" class="rounded-xl border-slate-200 text-sm"><option value="kasir">Kasir</option><option value="admin">Admin</option></select><input name="password" type="password" placeholder="Kata sandi" class="rounded-xl border-slate-200 text-sm" required><input name="password_confirmation" type="password" placeholder="Konfirmasi kata sandi" class="rounded-xl border-slate-200 text-sm" required><div><x-primary-button>Tambah pengguna</x-primary-button></div>
                    </form>
                    <div class="mt-6 space-y-3">@foreach ($users as $account)<div class="rounded-2xl border border-slate-100 p-4"><div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><p class="font-bold">{{ $account->name }} @if($account->is($user))<span class="text-xs font-normal text-slate-400">(Anda)</span>@endif</p><p class="text-sm text-slate-500">{{ $account->email }} · <span class="font-semibold text-teal-700">{{ ucfirst($account->role) }}</span></p></div><div class="flex gap-2"><form method="POST" action="{{ route('settings.users.update', $account) }}" class="flex items-center gap-2">@csrf @method('PATCH')<input type="hidden" name="name" value="{{ $account->name }}"><input type="hidden" name="email" value="{{ $account->email }}"><select name="role" class="rounded-lg border-slate-200 py-2 text-xs"><option value="kasir" @selected($account->role === 'kasir')>Kasir</option><option value="admin" @selected($account->role === 'admin')>Admin</option></select><input type="hidden" name="password" value=""><input type="hidden" name="password_confirmation" value=""><button class="rounded-lg px-3 py-2 text-xs font-bold text-teal-600 hover:bg-teal-50">Simpan peran</button></form>@if(!$account->is($user))<form method="POST" action="{{ route('settings.users.destroy', $account) }}" onsubmit="return confirm('Hapus pengguna ini?')">@csrf @method('DELETE')<button class="rounded-lg px-3 py-2 text-xs font-bold text-rose-500 hover:bg-rose-50">Hapus</button></form>@endif</div></div></div>@endforeach</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
