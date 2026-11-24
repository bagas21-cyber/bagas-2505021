<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /** @return array<string, mixed> */
    public function sales(Carbon $from, Carbon $to): array
    {
        $sales = $this->salesQuery($from, $to)
            ->with('user')
            ->select('sales.*')
            ->selectSub(
                SaleItem::query()
                    ->selectRaw('COALESCE(SUM(qty), 0)')
                    ->whereColumn('sale_id', 'sales.id'),
                'items_qty',
            )
            ->latest('date')
            ->get();

        $daily = $this->salesQuery($from, $to)
            ->selectRaw('DATE(date) as report_date, COUNT(*) as transactions, COALESCE(SUM(total), 0) as revenue')
            ->groupByRaw('DATE(date)')
            ->orderByDesc('report_date')
            ->get()
            ->map(fn ($day): array => [
                'date' => (string) $day->report_date,
                'label' => Carbon::parse($day->report_date)->locale('id')->isoFormat('D MMM'),
                'transactions' => (int) $day->transactions,
                'revenue' => (int) $day->revenue,
            ]);

        $summary = $this->salesQuery($from, $to)
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total), 0) as revenue')
            ->first();
        $quantity = $this->salesItemsQuery($from, $to)->sum('sale_items.qty');

        $topProducts = $this->productSalesQuery($from, $to)
            ->orderByDesc('report_sales.aggregate_revenue')
            ->limit(10)
            ->get()
            ->map(fn (Product $product): array => [
                'product' => $product,
                'quantity' => (int) $product->aggregate_quantity,
                'revenue' => (int) $product->aggregate_revenue,
            ]);

        return [
            'sales' => $sales,
            'daily' => $daily,
            'topProducts' => $topProducts,
            'metrics' => [
                'transactions' => (int) $summary->transactions,
                'revenue' => (int) $summary->revenue,
                'quantity' => (int) $quantity,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function stock(Carbon $from, Carbon $to): array
    {
        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest()
            ->get();

        $aggregate = StockMovement::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN qty ELSE 0 END), 0) as stock_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'out' THEN ABS(qty) ELSE 0 END), 0) as stock_out")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'adjustment' THEN qty ELSE 0 END), 0) as adjustment")
            ->first();

        return [
            'movements' => $movements,
            'summary' => [
                'in' => (int) $aggregate->stock_in,
                'out' => (int) $aggregate->stock_out,
                'adjustment' => (int) $aggregate->adjustment,
            ],
            'products' => Product::query()->with('category')->active()->orderBy('name')->get(),
            'lowStock' => Product::query()->with('category')->active()->lowStock()->orderBy('stock')->orderBy('name')->get(),
        ];
    }

    /** @return array<string, mixed> */
    public function profit(Carbon $from, Carbon $to): array
    {
        $rows = $this->productSalesQuery($from, $to)
            ->orderByRaw('(report_sales.aggregate_revenue - report_sales.aggregate_cost) DESC')
            ->get()
            ->map(fn (Product $product): array => [
                'product' => $product,
                'quantity' => (int) $product->aggregate_quantity,
                'revenue' => (int) $product->aggregate_revenue,
                'cost' => (int) $product->aggregate_cost,
                'profit' => (int) $product->aggregate_revenue - (int) $product->aggregate_cost,
            ]);

        $totals = $this->salesItemsQuery($from, $to)
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sale_items.qty * sale_items.buy_price), 0) as cost')
            ->first();

        return [
            'rows' => $rows,
            'totals' => [
                'revenue' => (int) $totals->revenue,
                'cost' => (int) $totals->cost,
                'profit' => (int) $totals->revenue - (int) $totals->cost,
            ],
        ];
    }

    public function dateRange(?string $from, ?string $to): array
    {
        $end = $to ? Carbon::parse($to) : now();
        $start = $from ? Carbon::parse($from) : $end->copy()->subDays(29);

        return [$start->startOfDay(), $end->endOfDay()];
    }

    private function salesQuery(Carbon $from, Carbon $to): Builder
    {
        return Sale::query()
            ->whereNull('voided_at')
            ->whereBetween('date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    private function salesItemsQuery(Carbon $from, Carbon $to): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.voided_at')
            ->whereBetween('sales.date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    private function productSalesQuery(Carbon $from, Carbon $to): Builder
    {
        $aggregates = SaleItem::query()
            ->select('sale_items.product_id')
            ->selectRaw('SUM(sale_items.qty) as aggregate_quantity')
            ->selectRaw('SUM(sale_items.subtotal) as aggregate_revenue')
            ->selectRaw('SUM(sale_items.qty * sale_items.buy_price) as aggregate_cost')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.voided_at')
            ->whereBetween('sales.date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('sale_items.product_id');

        return Product::query()
            ->joinSub($aggregates, 'report_sales', 'report_sales.product_id', '=', 'products.id')
            ->addSelect('products.*', 'report_sales.aggregate_quantity', 'report_sales.aggregate_revenue', 'report_sales.aggregate_cost');
    }
}
