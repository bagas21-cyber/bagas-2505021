<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            'store_name' => 'UMKMKu',
            'store_address' => '',
            'store_phone' => '',
            'currency' => 'IDR',
            'receipt_footer' => '',
            'timezone' => config('app.timezone', 'Asia/Jakarta'),
            'registration_enabled' => '0',
        ] as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'currency', 'receipt_footer', 'timezone', 'registration_enabled',
        ])->delete();
    }
};
