<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_stock_opname_updates_stock_and_records_adjustment_movement(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['stock' => 8]);

        $response = $this->actingAs($admin)->post(route('stock.adjustments.store'), [
            'date' => now()->toDateString(),
            'note' => 'Hasil hitung fisik',
            'items' => [[
                'product_id' => $product->id,
                'actual_stock' => 11,
            ]],
        ]);

        $stockOpname = StockOpname::firstOrFail();
        $response->assertRedirect(route('stock.adjustments.show', $stockOpname));
        $this->assertSame(11, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $stockOpname->id,
            'product_id' => $product->id,
            'system_stock' => 8,
            'actual_stock' => 11,
            'difference' => 3,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'qty' => 3,
            'stock_after' => 11,
            'reference_id' => $stockOpname->id,
            'user_id' => $admin->id,
            'note' => 'Penyesuaian stok',
        ]);
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Umum']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'code' => 'PRD-'.uniqid(),
            'name' => 'Produk Uji',
            'unit' => 'pcs',
            'buy_price' => 1000,
            'sell_price' => 1500,
            'stock' => 0,
            'min_stock' => 1,
            'is_active' => true,
        ], $overrides));
    }
}
