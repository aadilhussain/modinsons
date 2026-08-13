<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An internal rate, for the admin only.
     *
     * The public catalogue quotes on enquiry and publishes no rates, so this
     * column must never reach a public view — it exists so the owner can sort
     * and manage the catalogue by price.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('unit');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['price']);
            $table->dropColumn('price');
        });
    }
};
