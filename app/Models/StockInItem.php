<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInItem extends Model
{
    use HasFactory;

    protected $fillable = ['stock_in_id', 'product_id', 'qty', 'buy_price', 'subtotal'];

    protected function casts(): array
    {
        return ['qty' => 'integer', 'buy_price' => 'integer', 'subtotal' => 'integer'];
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}