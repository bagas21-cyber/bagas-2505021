<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_product_stock_in_sale_void_opname_and_report_flow(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.test']);
        $kasir = User::factory()->create(['role' => 'kasir', 'email' => 'kasir@example.test']);
        $this->post('/login', ['email' => $kasir->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));

        $category = Category::create(['name' => 'Minuman']);
        $this->actingAs($admin)->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Air Mineral',
            'unit' => 'botol',
            'buy_price' => 2000,
            'sell_price' => 3500,
            'stock' => 0,
            'min_stock' => 5,
            'is_active' => 1,
        ])->assertRedirect(route('products.index'));
        $product = Product::firstOrFail();

        $this->actingAs($kasir)->post(route('stock-ins.store'), [
            'invoice_no' => 'IN-FLOW-001',
            'date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'qty' => 10, 'buy_price' => 2000]],
        ])->assertRedirect();
        $this->assertSame(10, $product->fresh()->stock);

        $saleResponse = $this->actingAs($kasir)->post(route('sales.store'), [
            'invoice_no' => 'INV-FLOW-001',
            'date' => now()->format('Y-m-d H:i:s'),
            'paid' => 7000,
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ]);
        $sale = Sale::where('invoice_no', 'INV-FLOW-001')->firstOrFail();
        $saleResponse->assertRedirect(route('sales.show', $sale));
        $this->assertSame(8, $product->fresh()->stock);

        $this->actingAs($admin)->post(route('sales.void', $sale))->assertRedirect(route('sales.show', $sale));
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertNotNull($sale->fresh()->voided_at);

        $this->actingAs($admin)->post(route('stock.adjustments.store'), [
            'date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'actual_stock' => 7]],
        ])->assertRedirect();
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_opnames', 1);

        $this->actingAs($admin)->get(route('reports.sales', ['from' => now()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()
            ->assertDontSee('INV-FLOW-001');
        $this->assertDatabaseHas('stock_ins', ['invoice_no' => 'IN-FLOW-001']);
        $this->assertInstanceOf(StockOpname::class, StockOpname::first());
    }
}
