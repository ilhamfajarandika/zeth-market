<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('topup')->index(); // topup | account
            $table->string('name'); // "86 Diamonds", "Akun Mythic Glory #12"
            $table->string('group')->nullable(); // "Diamond", "Weekly Pass", ...
            $table->string('image')->nullable();
            $table->text('description')->nullable();

            // Khusus type=topup (dikirim ke supplier)
            $table->string('supplier')->nullable(); // digiflazz | vip | apigames
            $table->string('supplier_sku')->nullable()->index();

            $table->decimal('base_price', 15, 2)->default(0);     // harga modal
            $table->decimal('price', 15, 2)->default(0);          // harga jual member/guest
            $table->decimal('reseller_price', 15, 2)->nullable(); // harga jual reseller
            $table->decimal('strike_price', 15, 2)->nullable();   // harga coret (opsional)

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['supplier', 'supplier_sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
