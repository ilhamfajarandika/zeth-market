<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_type', 20); // snapshot: topup | account
            $table->string('product_name');      // snapshot nama produk saat order

            // Data tujuan top up (kosong untuk pembelian akun)
            $table->string('target_user_id')->nullable();
            $table->string('target_zone_id')->nullable();

            // Kontak pembeli (guest checkout)
            $table->string('email')->nullable();
            $table->string('whatsapp', 20)->nullable();

            $table->decimal('price', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('total', 15, 2);

            $table->string('status', 20)->default('pending')->index();

            // Pembayaran
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable()->index();
            $table->text('payment_url')->nullable();
            $table->text('payment_code')->nullable(); // VA number / QR string
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            // Supplier
            $table->string('supplier_reference')->nullable()->index();
            $table->string('supplier_status')->nullable();
            $table->text('supplier_message')->nullable();
            $table->text('serial_number')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
