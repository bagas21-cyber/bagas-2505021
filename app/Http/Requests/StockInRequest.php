<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))->map(function (array $item): array {
            foreach (['qty', 'buy_price'] as $field) {
                if (array_key_exists($field, $item)) {
                    $item[$field] = preg_replace('/\D+/', '', (string) $item[$field]);
                }
            }

            return $item;
        })->values()->all();
        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'invoice_no' => ['required', 'string', 'max:100', Rule::unique('stock_ins', 'invoice_no')->ignore($this->route('stock_in'))],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'update_buy_price' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.buy_price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'Supplier tidak ditemukan.',
            'invoice_no.required' => 'Nomor faktur wajib diisi.',
            'invoice_no.unique' => 'Nomor faktur sudah digunakan.',
            'date.required' => 'Tanggal stok masuk wajib diisi.',
            'date.date' => 'Tanggal stok masuk tidak valid.',
            'items.required' => 'Minimal satu produk harus ditambahkan.',
            'items.min' => 'Minimal satu produk harus ditambahkan.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.distinct' => 'Produk tidak boleh dipilih dua kali.',
            'items.*.product_id.exists' => 'Produk tidak ditemukan.',
            'items.*.qty.required' => 'Jumlah wajib diisi.',
            'items.*.qty.integer' => 'Jumlah harus berupa angka.',
            'items.*.qty.min' => 'Jumlah minimal 1.',
            'items.*.buy_price.required' => 'Harga beli wajib diisi.',
            'items.*.buy_price.integer' => 'Harga beli harus berupa angka.',
            'items.*.buy_price.min' => 'Harga beli tidak boleh negatif.',
        ];
    }
}
