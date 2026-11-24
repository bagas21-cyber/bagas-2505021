<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();
        $previousMonthEnd = $monthStart->copy()->subSecond();

        $todaySales = Sale::query()
            ->whereNull('voided_at')
            ->whereBetween('date', [$todayStart, $todayEnd])
            ->sum('total');
        $todayTransactions = Sale::query()
            ->whereNull('voided_at')
            ->whereBetween('date', [$todayStart, $todayEnd])
            ->count();
        $monthSales = Sale::query()
            ->whereNull('voided_at')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('total');
        $previousMonthSales = Sale::query()
            ->whereNull('voided_at')
            ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
            ->sum('total');
        $salesChange = $previousMonthSales > 0
            ? round((($monthSales - $previousMonthSales) / $previousMonthSales) * 100, 1)
            : ($monthSales > 0 ? 100 : 0);

        $salesByDate = Sale::query()
            ->selectRaw('DATE(date) as sales_date, SUM(total) as total')
            ->whereNull('voided_at')
            ->whereBetween('date', [$now->copy()->subDays(6)->startOfDay(), $todayEnd])
            ->groupByRaw('DATE(date)')
            ->pluck('total', 'sales_date');

        $chartLabels = [];
        $chartValues = [];
        for ($day = 6; $day >= 0; $day--) {
            $date = $now->copy()->subDays($day);
            $key = $date->toDateString();
            $chartLabels[] = $date->isoFormat('ddd');
            $chartValues[] = (int) ($salesByDate[$key] ?? 0);
        }

        $topProducts = SaleItem::query()
            ->select([
                'products.name',
                DB::raw('SUM(sale_items.qty) as quantity'),
                DB::raw('SUM(sale_items.subtotal) as revenue'),
            ])
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.voided_at')
            ->whereBetween('sales.date', [$monthStart, $monthEnd])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'todaySales' => $todaySales,
            'todayTransactions' => $todayTransactions,
            'monthSales' => $monthSales,
            'salesChange' => $salesChange,
            'productCount' => Product::query()->where('is_active', true)->count(),
            'lowStockCount' => Product::query()->where('is_active', true)->lowStock()->count(),
            'lowStockProducts' => Product::query()
                ->with('category:id,name')
                ->where('is_active', true)
                ->lowStock()
                ->orderBy('stock')
                ->limit(5)
                ->get(['id', 'category_id', 'code', 'name', 'stock', 'min_stock', 'unit']),
            'recentSales' => Sale::query()
                ->with('user:id,name')
                ->withCount('items')
                ->whereNull('voided_at')
                ->latest('date')
                ->limit(6)
                ->get(['id', 'user_id', 'invoice_no', 'date', 'total']),
            'topProducts' => $topProducts,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'greeting' => $this->greeting($now),
            'todayLabel' => $this->formatDate($now),
        ]);
    }

    private function greeting(Carbon $now): string
    {
        $hour = $now->hour;

        return match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    private function formatDate(Carbon $date): string
    {
        return $date->locale('id')->isoFormat('dddd, D MMMM YYYY');
    }
}
