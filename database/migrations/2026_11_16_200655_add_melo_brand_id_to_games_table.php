<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Tautan opsional ke brand katalog Melo (dipakai untuk sync item/produk).
            $table->unsignedInteger('melo_brand_id')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropUnique(['melo_brand_id']);
            $table->dropColumn('melo_brand_id');
        });
    }
};
