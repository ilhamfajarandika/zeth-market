<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('melo_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('sku_code')->unique();
            $table->string('name');
            $table->string('type_name')->nullable(); // jadi products.group saat dipilih
            $table->unsignedBigInteger('price')->default(0); // harga modal Melo, Rupiah penuh
            $table->string('status', 20); // status dari Melo: active, out_of_stock, ...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('melo_products');
    }
};
