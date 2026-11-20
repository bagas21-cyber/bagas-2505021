<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create(['name' => 'Admin UMKMKu', 'email' => 'admin@umkmku.test', 'password' => Hash::make('password'), 'role' => 'admin', 'email_verified_at' => now()]);
        $kasir = User::create(['name' => 'Kasir UMKMKu', 'email' => 'kasir@umkmku.test', 'password' => Hash::make('password'), 'role' => 'kasir', 'email_verified_at' => now()]);

        $categoryNames = ['Sembako', 'Minuman', 'Snack', 'Mie Instan', 'Kebutuhan Rumah', 'Bumbu Dapur'];
        $categories = collect($categoryNames)->mapWithKeys(fn (string $name) => [$name => Category::create(['name' => $name])]);

        $suppliers = collect([
            ['name' => 'CV Sumber Rezeki', 'phone' => '081234567890', 'address' => 'Jl. Pasar Induk No. 12, Jakarta'],
            ['name' => 'PT Mitra Niaga Sejahtera', 'phone' => '082233445566', 'address' => 'Jl. Raya Industri No. 8, Bekasi'],
            ['name' => 'UD Berkah Makmur', 'phone' => '083344556677', 'address' => 'Jl. Cibaduyut No. 21, Bandung'],
        ])->map(fn (array $supplier) => Supplier::create($supplier));

        $productRows = [
            ['Sembako', 'SMB-001', 'Beras Premium 5kg', 'karung', 68000, 76000],
            ['Sembako', 'SMB-002', 'Minyak Goreng Bimoli 1L', 'botol', 15500, 19000],
            ['Sembako', 'SMB-003', 'Gula Pasir 1kg', 'pak', 14500, 17500],
            ['Sembako', 'SMB-004', 'Telur Ayam 1kg', 'kg', 27000, 32000],
            ['Sembako', 'SMB-005', 'Tepung Terigu Segitiga Biru 1kg', 'pak', 12500, 16000],
            ['Minuman', 'MNM-001', 'Aqua 600ml', 'botol', 2500, 4000],
            ['Minuman', 'MNM-002', 'Teh Botol Sosro 450ml', 'botol', 3500, 5500],
            ['Minuman', 'MNM-003', 'Kopi Kapal Api 165gr', 'bungkus', 14500, 18500],
            ['Minuman', 'MNM-004', 'Susu UHT Ultra Milk 250ml', 'kotak', 4500, 6500],
            ['Snack', 'SNK-001', 'Chitato Sapi Panggang 68gr', 'bungkus', 8500, 11500],
            ['Snack', 'SNK-002', 'Beng-Beng 20gr', 'pcs', 1800, 2500],
            ['Snack', 'SNK-003', 'Oreo Original 119gr', 'bungkus', 7500, 10000],
            ['Snack', 'SNK-004', 'Kacang Garuda 100gr', 'bungkus', 6500, 9000],
            ['Mie Instan', 'MIE-001', 'Indomie Goreng', 'pcs', 2500, 3500],
            ['Mie Instan', 'MIE-002', 'Indomie Kuah Soto', 'pcs', 2400, 3500],
            ['Mie Instan', 'MIE-003', 'Mie Sedaap Goreng', 'pcs', 2400, 3500],
            ['Mie Instan', 'MIE-004', 'Pop Mie Ayam', 'cup', 5500, 7500],
            ['Kebutuhan Rumah', 'RTG-001', 'Rinso Detergen 800gr', 'bungkus', 18000, 22500],
            ['Kebutuhan Rumah', 'RTG-002', 'Sunlight Jeruk Nipis 755ml', 'botol', 13500, 17500],
            ['Kebutuhan Rumah', 'RTG-003', 'Lifebuoy Sabun Cair 250ml', 'botol', 14500, 19000],
            ['Kebutuhan Rumah', 'RTG-004', 'Pepsodent Pasta Gigi 190gr', 'tube', 10500, 14000],
            ['Bumbu Dapur', 'BMB-001', 'Bango Kecap Manis 600ml', 'botol', 22000, 28000],
            ['Bumbu Dapur', 'BMB-002', 'Masako Ayam 9gr', 'sachet', 700, 1000],
            ['Bumbu Dapur', 'BMB-003', 'Sasa Tepung Bumbu', 'sachet', 3500, 5000],
            ['Bumbu Dapur', 'BMB-004', 'Royco Kaldu Ayam 100gr', 'bungkus', 6500, 8500],
        ];

        $products = collect($productRows)->map(function (array $row, int $index) use ($categories): Product {
            [$category, $code, $name, $unit, $buyPrice, $sellPrice] = $row;
            $lowStock = $index < 3;
            return Product::create([
                'category_id' => $categories[$category]->id,
                'code' => $code,
                'name' => $name,
                'unit' => $unit,
                'buy_price' => $buyPrice,
                'sell_price' => $sellPrice,
                'stock' => 0,
                'min_stock' => $lowStock ? 10 : 10,
                'is_active' => true,
            ]);
        });

        Setting::insert([
            ['key' => 'store_name', 'value' => 'UMKMKu'],
            ['key' => 'store_address', 'value' => 'Jl. Mawar No. 10'],
            ['key' => 'store_phone', 'value' => '081234567890'],
        ]);

        $this->seedStockIns($admin, $suppliers, $products);
        $this->seedSales($admin, $kasir, $products);
    }

    private function seedStockIns(User $user, $suppliers, $products): void
    {
        foreach ($suppliers as $supplierIndex => $supplier) {
            $items = $products->slice($supplierIndex * 8, $supplierIndex === 2 ? 9 : 8);
            $stockIn = StockIn::create([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'invoice_no' => 'IN-'.now()->format('Ymd').'-'.str_pad((string) ($supplierIndex + 1), 3, '0', STR_PAD_LEFT),
                'date' => now()->subDays(31 - $supplierIndex)->toDateString(),
                'total' => $items->sum(fn (Product $product) => $product->buy_price * 100),
                'note' => 'Stok awal seeder',
            ]);

            foreach ($items as $product) {
                $qty = 100;
                StockInItem::create(['stock_in_id' => $stockIn->id, 'product_id' => $product->id, 'qty' => $qty, 'buy_price' => $product->buy_price, 'subtotal' => $qty * $product->buy_price]);
                $product->increment('stock', $qty);
                StockMovement::create(['product_id' => $product->id, 'type' => 'in', 'qty' => $qty, 'stock_after' => $product->fresh()->stock, 'reference_type' => StockIn::class, 'reference_id' => $stockIn->id, 'note' => 'Stok masuk awal', 'user_id' => $user->id]);
            }
        }

        $products->take(3)->each(function (Product $product, int $index) use ($user): void {
            $adjustment = [98, 97, 95][$index];
            $product->decrement('stock', $adjustment);
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'qty' => -$adjustment,
                'stock_after' => $product->fresh()->stock,
                'note' => 'Penyesuaian stok untuk produk menipis',
                'user_id' => $user->id,
            ]);
        });
    }

    private function seedSales(User $admin, User $kasir, $products): void
    {
        $saleProducts = $products->skip(3)->values();
        for ($saleNumber = 1; $saleNumber <= 120; $saleNumber++) {
            $date = now()->subDays(($saleNumber - 1) % 30)->setTime(8 + (($saleNumber * 3) % 12), ($saleNumber * 7) % 60);
            $items = $saleProducts->shuffle()->take(1 + (($saleNumber * 7) % 4));
            $saleItems = [];
            $total = 0;

            foreach ($items as $product) {
                $qty = 1 + (($saleNumber + $product->id) % 3);
                $subtotal = $qty * $product->sell_price;
                $saleItems[] = ['product' => $product, 'qty' => $qty, 'subtotal' => $subtotal];
                $total += $subtotal;
            }

            $paid = (int) (ceil($total / 5000) * 5000);
            $sale = Sale::create(['user_id' => $saleNumber % 2 === 0 ? $kasir->id : $admin->id, 'invoice_no' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) $saleNumber, 4, '0', STR_PAD_LEFT), 'date' => $date, 'total' => $total, 'paid' => $paid, 'change' => $paid - $total, 'note' => $saleNumber > 100 ? 'Penjualan ramai akhir periode' : null]);

            foreach ($saleItems as $item) {
                /** @var Product $product */
                $product = $item['product'];
                SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'qty' => $item['qty'], 'sell_price' => $product->sell_price, 'buy_price' => $product->buy_price, 'subtotal' => $item['subtotal']]);
                $product->decrement('stock', $item['qty']);
                StockMovement::create(['product_id' => $product->id, 'type' => 'out', 'qty' => -$item['qty'], 'stock_after' => $product->fresh()->stock, 'reference_type' => Sale::class, 'reference_id' => $sale->id, 'note' => 'Penjualan', 'user_id' => $sale->user_id]);
            }
        }
    }
}