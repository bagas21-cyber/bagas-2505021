<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIn extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_id', 'user_id', 'invoice_no', 'date', 'total', 'note', 'cancelled_at'];

    protected function casts(): array
    {
        return ['date' => 'date', 'total' => 'integer', 'cancelled_at' => 'datetime'];
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockInItem::class);
    }
}
