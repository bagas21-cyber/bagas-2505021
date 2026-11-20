<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'code', 'name', 'unit', 'buy_price', 'sell_price', 'stock', 'min_stock', 'image', 'is_active'];

    protected function casts(): array
    {
        return [
            'buy_price' => 'integer',
            'sell_price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'Habis';
        }

        if ($this->stock <= $this->min_stock / 2) {
            return 'Hampir Habis';
        }

        if ($this->stock <= $this->min_stock) {
            return 'Perlu Stok';
        }

        return 'Aman';
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image ? asset('storage/'.$this->image) : asset('images/product-placeholder.svg');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockInItems(): HasMany
    {
        return $this->hasMany(StockInItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }
}