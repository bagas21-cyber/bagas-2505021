<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(int $productId, int $requested, int $available)
    {
        parent::__construct("Stok produk #{$productId} tidak mencukupi. Diminta {$requested}, tersedia {$available}.");
    }
}
