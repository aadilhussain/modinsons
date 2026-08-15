<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quantity on hand, for wholesale/retail stock tracking.
     *
     * Nullable: products imported or added without a count opt out of stock
     * tracking entirely rather than reading as "0 in stock".
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_qty')->nullable()->after('min_order_qty');
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock_qty');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_qty', 'low_stock_threshold']);
        });
    }
};
