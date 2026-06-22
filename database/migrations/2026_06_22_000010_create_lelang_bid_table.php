<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lelang_bid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lelang_id')->constrained('lelang')->cascadeOnDelete();
            $table->foreignId('industri_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('harga', 15, 2);
            $table->boolean('is_buyout')->default(false);
            $table->timestamps();

            $table->index(['lelang_id', 'harga']);
            $table->index(['lelang_id', 'created_at']);
        });

        // FK pointer pemimpin ditambahkan setelah lelang_bid ada (hindari circular FK).
        Schema::table('lelang', function (Blueprint $table) {
            $table->foreign('highest_bid_id')->references('id')->on('lelang_bid')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lelang', function (Blueprint $table) {
            $table->dropForeign(['highest_bid_id']);
        });

        Schema::dropIfExists('lelang_bid');
    }
};
