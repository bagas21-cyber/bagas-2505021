<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = ['stock_opname_id', 'product_id', 'system_stock', 'actual_stock', 'difference'];

    protected function casts(): array
    {
        return ['system_stock' => 'integer', 'actual_stock' => 'integer', 'difference' => 'integer'];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
