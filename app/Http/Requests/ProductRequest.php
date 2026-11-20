<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        foreach (['buy_price', 'sell_price', 'stock', 'min_stock'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => preg_replace('/\D+/', '', (string) $this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        $product = $this->route('product');
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('products', 'code')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:30'],
            'buy_price' => ['required', 'integer', 'min:0'],
            'sell_price' => ['required', 'integer', 'min:0'],
            'stock' => [$product ? 'nullable' : 'required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.', 'category_id.exists' => 'Kategori tidak ditemukan.',
            'name.required' => 'Nama produk wajib diisi.', 'name.max' => 'Nama produk maksimal 150 karakter.',
            'unit.required' => 'Satuan wajib diisi.', 'buy_price.required' => 'Harga beli wajib diisi.',
            'buy_price.integer' => 'Harga beli harus berupa angka.', 'sell_price.required' => 'Harga jual wajib diisi.',
            'sell_price.integer' => 'Harga jual harus berupa angka.', 'stock.required' => 'Stok awal wajib diisi.',
            'stock.integer' => 'Stok awal harus berupa angka.', 'min_stock.required' => 'Minimum stok wajib diisi.',
            'min_stock.integer' => 'Minimum stok harus berupa angka.', 'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.', 'image.max' => 'Ukuran gambar maksimal 2 MB.',
            'code.unique' => 'Kode produk sudah digunakan.',
        ];
    }
}
