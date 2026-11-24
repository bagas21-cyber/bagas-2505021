<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function increase(Product|int $product, int $quantity, ?Model $reference = null, ?int $userId = null, ?string $note = null): Product
    {
        return $this->change($product, $quantity, 'in', $reference, $userId, $note);
    }

    public function decrease(Product|int $product, int $quantity, ?Model $reference = null, ?int $userId = null, ?string $note = null): Product
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Jumlah stok harus positif.');
        }

        return DB::transaction(function () use ($product, $quantity, $reference, $userId, $note): Product {
            $locked = $this->lockedProduct($product);
            if ($locked->stock < $quantity) {
                throw new InsufficientStockException($locked->id, $quantity, $locked->stock);
            }

            $locked->stock -= $quantity;
            $locked->save();
            $this->movement($locked, -$quantity, 'out', $reference, $userId, $note);

            return $locked->fresh();
        });
    }

    public function adjust(Product|int $product, int $targetStock, ?Model $reference = null, ?int $userId = null, ?string $note = null): Product
    {
        if ($targetStock < 0) {
            throw new \InvalidArgumentException('Stok tidak boleh negatif.');
        }

        return DB::transaction(function () use ($product, $targetStock, $reference, $userId, $note): Product {
            $locked = $this->lockedProduct($product);
            $delta = $targetStock - $locked->stock;
            if ($delta === 0) {
                return $locked;
            }

            $locked->stock = $targetStock;
            $locked->save();
            $this->movement($locked, $delta, 'adjustment', $reference, $userId, $note);

            return $locked->fresh();
        });
    }

    private function change(Product|int $product, int $quantity, string $type, ?Model $reference, ?int $userId, ?string $note): Product
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Jumlah stok harus positif.');
        }

        return DB::transaction(function () use ($product, $quantity, $type, $reference, $userId, $note): Product {
            $locked = $this->lockedProduct($product);
            $locked->stock += $quantity;
            $locked->save();
            $this->movement($locked, $quantity, $type, $reference, $userId, $note);

            return $locked->fresh();
        });
    }

    private function lockedProduct(Product|int $product): Product
    {
        return Product::query()->whereKey($product instanceof Product ? $product->getKey() : $product)->lockForUpdate()->firstOrFail();
    }

    private function movement(Product $product, int $quantity, string $type, ?Model $reference, ?int $userId, ?string $note): void
    {
        $product->stockMovements()->create([
            'type' => $type,
            'qty' => $quantity,
            'stock_after' => $product->stock,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'user_id' => $userId,
        ]);
    }
}
