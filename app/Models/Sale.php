<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'invoice_no', 'date', 'total', 'paid', 'change', 'note', 'voided_at'];

    protected function casts(): array
    {
        return ['date' => 'datetime', 'total' => 'integer', 'paid' => 'integer', 'change' => 'integer', 'voided_at' => 'datetime'];
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}