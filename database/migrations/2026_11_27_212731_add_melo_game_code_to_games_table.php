<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Kode game untuk cek nickname Melo (games[].game_code dari GET /check-nickname/games), bukan slug brand.
            $table->string('melo_game_code')->nullable()->after('melo_brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('melo_game_code');
        });
    }
};
