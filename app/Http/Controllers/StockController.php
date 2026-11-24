<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function low(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $products = Product::query()
            ->with('category')
            ->active()
            ->lowStock()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')))
            ->orderBy('stock')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('stock.low', compact('products', 'search'));
    }

    public function movements(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $type = (string) $request->query('type', '');
        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search): void {
                $q->where('note', 'like', '%'.$search.'%')
                    ->orWhereHas('product', fn ($product) => $product
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%'));
            }))
            ->when(in_array($type, ['in', 'out', 'adjustment'], true), fn ($query) => $query->where('type', $type))
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('stock.movements', compact('movements', 'search', 'type'));
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('stock.adjustments.create', [
            'products' => Product::active()->orderBy('name')->get(),
            'stockOpname' => new StockOpname(['date' => now()->toDateString()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.actual_stock' => ['required', 'integer', 'min:0'],
        ]);

        $items = $data['items'];
        unset($data['items']);
        $stockOpname = DB::transaction(function () use ($data, $items, $request): StockOpname {
            $products = Product::query()
                ->whereIn('id', collect($items)->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $stockOpname = StockOpname::create([...$data, 'user_id' => $request->user()->id]);

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                abort_if($product === null, 422, 'Produk tidak ditemukan.');
                $systemStock = (int) $product->stock;
                $actualStock = (int) $item['actual_stock'];
                $stockOpname->items()->create([
                    'product_id' => $product->id,
                    'system_stock' => $systemStock,
                    'actual_stock' => $actualStock,
                    'difference' => $actualStock - $systemStock,
                ]);
                $this->stockService->adjust($product, $actualStock, $stockOpname, $request->user()->id, 'Penyesuaian stok');
            }

            return $stockOpname;
        });

        return to_route('stock.adjustments.show', $stockOpname)
            ->with('success', 'Penyesuaian stok berhasil dicatat.');
    }

    public function show(StockOpname $stockOpname): View
    {
        $stockOpname->load(['user', 'items.product']);

        return view('stock.adjustments.show', compact('stockOpname'));
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
