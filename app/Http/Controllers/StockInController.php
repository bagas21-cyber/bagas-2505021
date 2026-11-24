<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StockInRequest;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', '');
        $stockIns = StockIn::query()->with('supplier')->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('invoice_no', 'like', '%'.$search.'%')->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$search.'%'))))
            ->when($status === 'active', fn ($query) => $query->whereNull('cancelled_at'))
            ->when($status === 'cancelled', fn ($query) => $query->whereNotNull('cancelled_at'))
            ->latest('date')->latest('id')->paginate(10)->withQueryString();

        return view('stock-ins.index', compact('stockIns', 'search', 'status'));
    }

    public function create(): View
    {
        return view('stock-ins.create', ['suppliers' => Supplier::orderBy('name')->get(), 'products' => Product::active()->orderBy('name')->get(), 'stockIn' => new StockIn(['date' => now()->toDateString()])]);
    }

    public function store(StockInRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items'], $data['update_buy_price']);
        $data['user_id'] = $request->user()->id;
        $data['total'] = collect($items)->sum(fn (array $item) => (int) $item['qty'] * (int) $item['buy_price']);

        $stockIn = DB::transaction(function () use ($data, $items, $request): StockIn {
            $stockIn = StockIn::create($data);
            $products = Product::query()
                ->whereIn('id', collect($items)->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                abort_if($product === null, 422, 'Produk tidak ditemukan.');
                $stockIn->items()->create([...$item, 'subtotal' => $item['qty'] * $item['buy_price']]);
                $this->stockService->increase($product, (int) $item['qty'], $stockIn, $request->user()->id, 'Stok masuk '.$stockIn->invoice_no);
                if ($request->boolean('update_buy_price')) {
                    $product->update(['buy_price' => $item['buy_price']]);
                }
            }

            return $stockIn;
        });

        return to_route('stock-ins.show', $stockIn)->with('success', 'Stok masuk berhasil dicatat.');
    }

    public function show(StockIn $stockIn): View
    {
        $stockIn->load(['supplier', 'user', 'items.product']);

        return view('stock-ins.show', compact('stockIn'));
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q'));

        return response()->json(Product::active()->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))->orderBy('name')->limit(10)->get(['id', 'code', 'name', 'unit', 'buy_price']));
    }

    public function cancel(Request $request, StockIn $stockIn): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if ($stockIn->isCancelled()) {
            return back()->with('error', 'Stok masuk sudah dibatalkan.');
        }

        try {
            DB::transaction(function () use ($stockIn, $request): void {
                $stockIn = StockIn::query()->with('items')->lockForUpdate()->findOrFail($stockIn->id);
                foreach ($stockIn->items as $item) {
                    $this->stockService->decrease($item->product_id, $item->qty, $stockIn, $request->user()->id, 'Pembatalan stok masuk '.$stockIn->invoice_no);
                }
                $stockIn->update(['cancelled_at' => now()]);
            });
        } catch (InsufficientStockException $exception) {
            return back()->with('error', 'Stok tidak mencukupi untuk membatalkan transaksi ini.');
        }

        return to_route('stock-ins.show', $stockIn)->with('success', 'Stok masuk berhasil dibatalkan dan stok dikembalikan.');
    }
}
