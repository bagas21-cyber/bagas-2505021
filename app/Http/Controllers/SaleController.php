<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', '');
        $sales = Sale::query()->with('user')->withCount('items')
            ->when($search !== '', fn ($query) => $query->where('invoice_no', 'like', '%'.$search.'%'))
            ->when($status === 'active', fn ($query) => $query->whereNull('voided_at'))
            ->when($status === 'voided', fn ($query) => $query->whereNotNull('voided_at'))
            ->latest('date')->latest('id')->paginate(10)->withQueryString();

        return view('sales.index', compact('sales', 'search', 'status'));
    }

    public function create(): View
    {
        return view('sales.create', ['products' => Product::active()->orderBy('name')->get(), 'sale' => new Sale(['date' => now()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_no' => ['nullable', 'string', 'max:100', 'unique:sales,invoice_no'],
            'date' => ['nullable', 'date'],
            'paid' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);
        $items = $data['items'];
        unset($data['items']);
        $data['user_id'] = $request->user()->id;
        $data['invoice_no'] = $data['invoice_no'] ?: $this->nextInvoiceNumber();
        $data['date'] = $data['date'] ?: now();

        $sale = DB::transaction(function () use ($data, $items, $request): Sale {
            $products = Product::query()->whereIn('id', collect($items)->pluck('product_id'))->get()->keyBy('id');
            $total = collect($items)->sum(fn (array $item): int => (int) $item['qty'] * (int) $products[$item['product_id']]->sell_price);
            abort_if((int) $data['paid'] < $total, 422, 'Pembayaran kurang dari total penjualan.');
            $sale = Sale::create([...$data, 'total' => $total, 'change' => (int) $data['paid'] - $total]);

            foreach ($items as $item) {
                $product = $products[$item['product_id']];
                $qty = (int) $item['qty'];
                $sale->items()->create(['product_id' => $product->id, 'qty' => $qty, 'sell_price' => $product->sell_price, 'buy_price' => $product->buy_price, 'subtotal' => $qty * $product->sell_price]);
                $this->stockService->decrease($product, $qty, $sale, $request->user()->id, 'Penjualan '.$sale->invoice_no);
            }

            return $sale;
        });

        return to_route('sales.show', $sale)->with('success', 'Penjualan berhasil dicatat.');
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $last = Sale::query()->where('invoice_no', 'like', $prefix.'%')->lockForUpdate()->latest('id')->value('invoice_no');
        $number = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    public function show(Sale $sale): View
    {
        $sale->load(['user', 'items.product']);
        return view('sales.show', compact('sale'));
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q'));
        return response()->json(Product::active()->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))->orderBy('name')->limit(10)->get(['id', 'code', 'name', 'unit', 'sell_price', 'stock']));
    }

    public function void(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if ($sale->isVoided()) {
            return back()->with('error', 'Penjualan sudah dibatalkan.');
        }

        try {
            DB::transaction(function () use ($sale, $request): void {
                $sale = Sale::query()->with('items')->lockForUpdate()->findOrFail($sale->id);
                if ($sale->isVoided()) {
                    return;
                }
                foreach ($sale->items as $item) {
                    $this->stockService->increase($item->product_id, $item->qty, $sale, $request->user()->id, 'Pembatalan penjualan '.$sale->invoice_no);
                }
                $sale->update(['voided_at' => now()]);
            });
        } catch (InsufficientStockException $exception) {
            return back()->with('error', 'Stok tidak mencukupi untuk membatalkan penjualan ini.');
        }

        return to_route('sales.show', $sale)->with('success', 'Penjualan berhasil dibatalkan dan stok dikembalikan.');
    }
}
