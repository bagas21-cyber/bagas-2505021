<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_displays_aggregated_numbers_for_the_selected_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();
        $date = Carbon::parse('2026-11-10 10:00:00');
        $sale = Sale::create([
            'user_id' => $admin->id,
            'invoice_no' => 'INV-REPORT-001',
            'date' => $date,
            'total' => 30000,
            'paid' => 30000,
            'change' => 0,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'qty' => 2,
            'sell_price' => 15000,
            'buy_price' => 10000,
            'subtotal' => 30000,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.sales', ['from' => '2026-11-10', 'to' => '2026-11-10']));

        $response->assertOk()
            ->assertSee('30.000')
            ->assertSee('INV-REPORT-001')
            ->assertSee('2');
    }

    public function test_profit_and_stock_reports_display_sql_aggregated_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();
        $date = Carbon::parse('2026-11-10 10:00:00');
        $sale = Sale::create([
            'user_id' => $admin->id,
            'invoice_no' => 'INV-REPORT-002',
            'date' => $date,
            'total' => 45000,
            'paid' => 45000,
            'change' => 0,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'qty' => 3,
            'sell_price' => 15000,
            'buy_price' => 10000,
            'subtotal' => 45000,
        ]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'in', 'qty' => 10, 'stock_after' => 10, 'user_id' => $admin->id, 'created_at' => $date]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'out', 'qty' => -3, 'stock_after' => 7, 'user_id' => $admin->id, 'created_at' => $date]);

        $this->actingAs($admin)
            ->get(route('reports.profit', ['from' => '2026-11-10', 'to' => '2026-11-10']))
            ->assertOk()
            ->assertSee('45.000')
            ->assertSee('30.000');

        $this->actingAs($admin)
            ->get(route('reports.stock', ['from' => '2026-11-10', 'to' => '2026-11-10']))
            ->assertOk()
            ->assertSee('10')
            ->assertSee('3');
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Umum']);

        return Product::create([
            'category_id' => $category->id,
            'code' => 'PRD-REPORT',
            'name' => 'Produk Laporan',
            'unit' => 'pcs',
            'buy_price' => 10000,
            'sell_price' => 15000,
            'stock' => 7,
            'min_stock' => 1,
            'is_active' => true,
        ]);
    }
}
