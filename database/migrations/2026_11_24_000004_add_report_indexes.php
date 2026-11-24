<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            if (! Schema::hasIndex('sales', 'sales_date_index')) {
                $table->index('date', 'sales_date_index');
            }
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            if (! Schema::hasIndex('sale_items', 'sale_items_product_id_index')) {
                $table->index('product_id', 'sale_items_product_id_index');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            if (! Schema::hasIndex('stock_movements', 'stock_movements_product_id_index')) {
                $table->index('product_id', 'stock_movements_product_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            if (Schema::hasIndex('sales', 'sales_date_index')) {
                $table->dropIndex('sales_date_index');
            }
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            if (Schema::hasIndex('sale_items', 'sale_items_product_id_index')) {
                $table->dropIndex('sale_items_product_id_index');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            if (Schema::hasIndex('stock_movements', 'stock_movements_product_id_index')) {
                $table->dropIndex('stock_movements_product_id_index');
            }
        });
    }
};
