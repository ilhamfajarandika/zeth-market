<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stok akun: 1 baris = 1 akun yang bisa dijual.
        Schema::create('game_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('credentials');             // terenkripsi (cast 'encrypted')
            $table->text('public_info')->nullable(); // info yang boleh tampil: rank, skin, level
            $table->string('status', 20)->default('available'); // available | reserved | sold | disabled
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'reserved_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_accounts');
    }
};
