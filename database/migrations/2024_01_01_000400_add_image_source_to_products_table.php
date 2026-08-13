<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a product photo came from.
 *
 * Kept alongside image_path so a photo can be re-fetched at a larger size, and
 * so there is a record of the source if a supplier ever asks how their image
 * came to be on the site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_source', 2048)->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_source');
        });
    }
};
