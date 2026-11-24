<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $type,
        private readonly array $data,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function array(): array
    {
        return self::rows($this->type, $this->data);
    }

    public function headings(): array
    {
        return self::headings($this->type);
    }

    public function title(): string
    {
        return ucfirst($this->type);
    }

    public static function headings(string $type): array
    {
        return match ($type) {
            'stock' => ['Waktu', 'Kode', 'Produk', 'Tipe', 'Perubahan', 'Stok Setelah', 'Dicatat Oleh', 'Catatan'],
            'profit' => ['Kode', 'Produk', 'Qty', 'Penjualan', 'Modal', 'Laba'],
            default => ['Tanggal', 'Invoice', 'Kasir', 'Qty Item', 'Penjualan'],
        };
    }

    public static function rows(string $type, array $data): array
    {
        if ($type === 'stock') {
            return $data['movements']->map(fn ($movement): array => [
                $movement->created_at->format('Y-m-d H:i'),
                $movement->product?->code,
                $movement->product?->name,
                $movement->type,
                $movement->qty,
                $movement->stock_after,
                $movement->user?->name,
                $movement->note,
            ])->all();
        }

        if ($type === 'profit') {
            return $data['rows']->map(fn (array $row): array => [
                $row['product']?->code,
                $row['product']?->name,
                $row['quantity'],
                $row['revenue'],
                $row['cost'],
                $row['profit'],
            ])->all();
        }

        return $data['sales']->map(fn ($sale): array => [
            $sale->date->format('Y-m-d H:i'),
            $sale->invoice_no,
            $sale->user?->name,
            $sale->items_qty,
            $sale->total,
        ])->all();
    }
}
