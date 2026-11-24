<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled_by_default(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Public User',
            'email' => 'public@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_kasir_cannot_open_admin_pages_or_mutate_master_data(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $category = Category::create(['name' => 'Test']);

        $this->actingAs($kasir)->get('/reports/sales')->assertForbidden();
        $this->actingAs($kasir)->get('/settings')->assertForbidden();
        $this->actingAs($kasir)->get('/categories/create')->assertForbidden();
        $this->actingAs($kasir)->delete('/categories/'.$category->id)->assertForbidden();
    }

    public function test_admin_can_update_settings_and_create_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/settings/store', [
            'store_name' => 'Toko Baru',
            'store_address' => 'Jl. Baru',
            'store_phone' => '0800',
        ])->assertRedirect(route('settings.index', ['tab' => 'store']));
        $this->assertSame('Toko Baru', Setting::where('key', 'store_name')->value('value'));

        $this->actingAs($admin)->post('/settings/users', [
            'name' => 'Kasir Baru',
            'email' => 'kasir-baru@example.com',
            'role' => 'kasir',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('settings.index', ['tab' => 'users']));
        $this->assertDatabaseHas('users', ['email' => 'kasir-baru@example.com', 'role' => 'kasir']);
    }

    public function test_registration_can_be_enabled_by_admin_setting(): void
    {
        Setting::where('key', 'registration_enabled')->update(['value' => '1']);

        $this->get('/register')->assertOk();
    }
}
