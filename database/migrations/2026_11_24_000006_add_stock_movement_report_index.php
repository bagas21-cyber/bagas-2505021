<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            if (! Schema::hasIndex('stock_movements', 'stock_movements_created_at_index')) {
                $table->index('created_at', 'stock_movements_created_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            if (Schema::hasIndex('stock_movements', 'stock_movements_created_at_index')) {
                $table->dropIndex('stock_movements_created_at_index');
            }
        });
    }
};
