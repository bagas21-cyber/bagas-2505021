<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'profile');
        abort_unless(in_array($tab, ['profile', 'store', 'users', 'preferences'], true), 404);

        return view('settings.index', [
            'tab' => $tab,
            'user' => $request->user(),
            'users' => $tab === 'users' ? User::query()->orderByDesc('role')->orderBy('name')->get() : collect(),
            'settings' => $this->settings->all(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);
        $user = $request->user();
        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return to_route('settings.index', ['tab' => 'profile'])->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'store_phone' => ['nullable', 'string', 'max:30'],
        ]);
        foreach ($data as $key => $value) {
            $this->settings->set($key, $value);
        }

        return to_route('settings.index', ['tab' => 'store'])->with('success', 'Informasi toko berhasil disimpan.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'currency' => ['required', 'string', 'max:10'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'timezone' => ['required', 'timezone'],
        ]);
        foreach ($data as $key => $value) {
            $this->settings->set($key, $value);
        }

        return to_route('settings.index', ['tab' => 'preferences'])->with('success', 'Preferensi berhasil disimpan.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'kasir'])],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data + ['email_verified_at' => now()]);

        return to_route('settings.index', ['tab' => 'users'])->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'kasir'])],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);
        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan peran admin yang sedang digunakan.']);
        }
        if ($user->isAdmin() && $data['role'] !== 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Minimal harus ada satu admin.']);
        }
        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);

        return to_route('settings.index', ['tab' => 'users'])->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if ($user->isAdmin() && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'Minimal harus ada satu admin.']);
        }
        if ($user->sales()->exists() || $user->stockIns()->exists() || $user->stockOpnames()->exists()) {
            return back()->withErrors(['user' => 'Pengguna dengan riwayat transaksi tidak dapat dihapus.']);
        }
        $user->delete();

        return to_route('settings.index', ['tab' => 'users'])->with('success', 'Pengguna berhasil dihapus.');
    }
}
