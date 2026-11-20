<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_service_increases_stock_and_records_movement(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['role' => 'admin']);
        $updated = app(StockService::class)->increase($product, 7, null, $user->id, 'Penerimaan');

        $this->assertSame(7, $updated->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'in', 'qty' => 7, 'stock_after' => 7]);
    }

    public function test_stock_in_creation_updates_stock_and_total(): void
    {
        $user = User::factory()->create(['role' => 'kasir']);
        $product = $this->product();
        $response = $this->actingAs($user)->post(route('stock-ins.store'), ['invoice_no' => 'IN-001', 'date' => now()->toDateString(), 'items' => [['product_id' => $product->id, 'qty' => 3, 'buy_price' => 1250]]]);

        $stockIn = StockIn::firstOrFail();
        $response->assertRedirect(route('stock-ins.show', $stockIn));
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(3750, $stockIn->total);
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $stockIn->id, 'qty' => 3]);
    }

    public function test_failed_decrease_rolls_back_stock_and_movement(): void
    {
        $product = $this->product(['stock' => 2]);
        $this->expectException(InsufficientStockException::class);
        try {
            app(StockService::class)->decrease($product, 3);
        } finally {
            $this->assertSame(2, $product->fresh()->stock);
            $this->assertDatabaseCount('stock_movements', 0);
        }
    }

    public function test_admin_can_cancel_stock_in_only_when_stock_is_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['stock' => 5]);
        $stockIn = StockIn::create(['user_id' => $admin->id, 'invoice_no' => 'IN-002', 'date' => now()->toDateString(), 'total' => 5000]);
        $stockIn->items()->create(['product_id' => $product->id, 'qty' => 5, 'buy_price' => 1000, 'subtotal' => 5000]);

        $this->actingAs($admin)->post(route('stock-ins.cancel', $stockIn))->assertRedirect(route('stock-ins.show', $stockIn));
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertNotNull($stockIn->fresh()->cancelled_at);
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $stockIn->id, 'type' => 'out', 'qty' => -5]);
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Umum']);
        return Product::create(array_merge(['category_id' => $category->id, 'code' => 'PRD-'.uniqid(), 'name' => 'Produk Uji', 'unit' => 'pcs', 'buy_price' => 1000, 'sell_price' => 1500, 'stock' => 0, 'min_stock' => 1, 'is_active' => true], $overrides));
    }
}
