<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Semua produk kini topup. Akun game dipisah ke tabel game_accounts sendiri (1 baris = 1 akun yang dijual).
     * Data akun lama dibuang atas keputusan PO (belum ada data penting); kredensial tidak disimpan lagi.
     */
    public function up(): void
    {
        Schema::dropIfExists('game_accounts');

        DB::table('orders')->where('product_type', 'account')->delete();
        DB::table('products')->where('type', 'account')->delete();

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });

        Schema::create('game_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price'); // harga jual Rupiah penuh, diisi admin
            $table->text('public_info')->nullable(); // info yang boleh tampil: rank, skin, level
            $table->string('status', 20)->default('available'); // available | sold | disabled
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_accounts');

        Schema::table('orders', function (Blueprint $table) {
            $table->string('product_type', 20)->default('topup')->after('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->default('topup')->after('game_id')->index();
        });

        Schema::create('game_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('credentials');
            $table->text('public_info')->nullable();
            $table->string('status', 20)->default('available');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'reserved_until']);
        });
    }
};
