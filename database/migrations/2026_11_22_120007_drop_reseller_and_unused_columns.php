<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reseller di luar scope (PO 2026-11-12); role-nya dihapus dari enum UserRole.
        DB::table('users')->where('role', 'reseller')->update(['role' => 'member']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['reseller_price', 'sort', 'image', 'description']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->after('role');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('group');
            $table->text('description')->nullable()->after('image');
            $table->decimal('reseller_price', 15, 2)->nullable()->after('price');
            $table->unsignedInteger('sort')->default(0)->after('is_active');
        });
    }
};
