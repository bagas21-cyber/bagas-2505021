<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $category = $request->integer('category');
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'name');
        $products = Product::query()->with('category')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))
            ->when($category, fn ($query) => $query->where('category_id', $category))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'low', fn ($query) => $query->whereColumn('stock', '<=', 'min_stock'))
            ->when($status === 'out', fn ($query) => $query->where('stock', '<=', 0))
            ->when($sort === 'stock_asc', fn ($query) => $query->orderBy('stock'))
            ->when($sort === 'stock_desc', fn ($query) => $query->orderByDesc('stock'))
            ->when($sort === 'newest', fn ($query) => $query->latest())
            ->when(! in_array($sort, ['stock_asc', 'stock_desc', 'newest'], true), fn ($query) => $query->orderBy('name'))
            ->paginate(10)->withQueryString();

        return view('products.index', ['products' => $products, 'categories' => Category::orderBy('name')->get(), 'search' => $search, 'selectedCategory' => $category, 'status' => $status, 'sort' => $sort]);
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('products.create', ['product' => new Product(['unit' => 'pcs', 'min_stock' => 5]), 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validated();
        unset($data['remove_image']);
        $data['code'] = $data['code'] ?? $this->nextCode($data['category_id']);
        $data['is_active'] = $request->boolean('is_active', true);
        $image = $request->file('image');
        unset($data['image']);

        DB::transaction(function () use (&$data, $image, $request): void {
            $product = Product::create($data);
            if ($image) {
                $product->update(['image' => $image->store('products', 'public')]);
            }
            if ($product->stock > 0) {
                StockMovement::create(['product_id' => $product->id, 'type' => 'in', 'qty' => $product->stock, 'stock_after' => $product->stock, 'note' => 'Stok awal produk', 'user_id' => $request->user()->id]);
            }
        });

        return to_route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        $product->load('category')->load(['stockMovements' => fn ($query) => $query->latest()->limit(10), 'stockMovements.user']);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->ensureAdmin();

        return view('products.edit', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validated();
        $image = $request->file('image');
        unset($data['image'], $data['stock'], $data['remove_image']);
        $data['is_active'] = $request->boolean('is_active');
        $oldImage = $product->image;
        if ($image) {
            $data['image'] = $image->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }
        $product->update($data);
        if (($image || $request->boolean('remove_image')) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return to_route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->ensureAdmin();
        if ($product->saleItems()->exists() || $product->stockInItems()->exists()) {
            $product->update(['is_active' => false]);

            return to_route('products.index')->with('success', 'Produk dinonaktifkan karena memiliki riwayat transaksi.');
        }
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return to_route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function nextCode(int $categoryId): string
    {
        $prefix = strtoupper(substr((string) Category::findOrFail($categoryId)->name, 0, 3));
        $number = 1;
        do {
            $code = $prefix.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $number++;
        } while (Product::where('code', $code)->exists());

        return $code;
    }
}
