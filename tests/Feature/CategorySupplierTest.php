<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created_updated_and_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('categories.store'), ['name' => 'Minuman'])
            ->assertSessionHas('success')->assertRedirect(route('categories.index'));
        $category = Category::query()->where('name', 'Minuman')->firstOrFail();

        $this->actingAs($user)->put(route('categories.update', $category), ['name' => 'Minuman Dingin'])
            ->assertSessionHas('success')->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Minuman Dingin']);

        $this->actingAs($user)->delete(route('categories.destroy', $category))
            ->assertSessionHas('success')->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_validation_uses_indonesian_messages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('categories.create'))
            ->post(route('categories.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Nama kategori wajib diisi.'])
            ->assertRedirect(route('categories.create'));
    }

    public function test_category_cannot_be_deleted_when_used_by_product(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Elektronik']);
        Product::create([
            'category_id' => $category->id,
            'code' => 'EL-001',
            'name' => 'Kabel',
            'unit' => 'pcs',
            'buy_price' => 1000,
            'sell_price' => 1500,
            'stock' => 2,
            'min_stock' => 1,
        ]);

        $this->actingAs($user)->delete(route('categories.destroy', $category))
            ->assertSessionHas('error')->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_supplier_can_be_created_updated_and_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'CV Sumber Jaya', 'phone' => '08123456789', 'address' => 'Bandung',
        ])->assertSessionHas('success')->assertRedirect(route('suppliers.index'));
        $supplier = Supplier::query()->where('name', 'CV Sumber Jaya')->firstOrFail();

        $this->actingAs($user)->put(route('suppliers.update', $supplier), [
            'name' => 'PT Sumber Jaya', 'phone' => '08987654321', 'address' => 'Jakarta',
        ])->assertSessionHas('success')->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'PT Sumber Jaya', 'phone' => '08987654321']);

        $this->actingAs($user)->delete(route('suppliers.destroy', $supplier))
            ->assertSessionHas('success')->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_validation_and_delete_guard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from(route('suppliers.create'))
            ->post(route('suppliers.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Nama supplier wajib diisi.'])
            ->assertRedirect(route('suppliers.create'));

        $supplier = Supplier::create(['name' => 'Pemasok Tetap']);
        $supplier->stockIns()->create([
            'user_id' => $user->id,
            'invoice_no' => 'INV-001',
            'date' => now()->toDateString(),
            'total' => 10000,
        ]);

        $this->actingAs($user)->delete(route('suppliers.destroy', $supplier))
            ->assertSessionHas('error')->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
