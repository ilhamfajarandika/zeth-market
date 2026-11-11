<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('publisher')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->text('instruction')->nullable(); // cara menemukan User ID, dll.

            // Tanpa cek nickname / calc: cukup validasi format input di server.
            $table->boolean('requires_zone_id')->default(false); // MLBB: true
            $table->string('user_id_label')->default('User ID');
            $table->string('zone_id_label')->nullable();
            $table->string('user_id_regex')->nullable(); // contoh: ^\d{6,12}$
            $table->string('zone_id_regex')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
