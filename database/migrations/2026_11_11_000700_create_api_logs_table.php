<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log request/callback ke payment gateway & supplier (debug + audit).
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);  // tripay | digiflazz | ...
            $table->string('direction', 10); // outgoing | callback
            $table->string('reference')->nullable()->index();
            $table->string('endpoint')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
