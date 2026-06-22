<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lelang', function (Blueprint $table) {
            $table->id();
            // Satu bahan baku hanya boleh punya satu lelang.
            $table->foreignId('bahan_baku_id')->unique()->constrained('bahan_baku')->cascadeOnDelete();
            $table->decimal('harga_awal', 15, 2);
            $table->decimal('kelipatan', 15, 2)->default(1000);
            $table->decimal('harga_reserve', 15, 2)->nullable();
            $table->decimal('harga_buyout', 15, 2)->nullable();
            $table->string('status')->default('berlangsung');
            $table->timestamp('waktu_mulai');
            $table->timestamp('waktu_selesai');
            // Pointer ke bid pemimpin; FK ditambahkan di migration lelang_bid.
            $table->unsignedBigInteger('highest_bid_id')->nullable();
            $table->foreignId('pemenang_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('harga_final', 15, 2)->nullable();
            // Settlement (pesanan deal) yang dibuat saat lelang menang.
            $table->unsignedBigInteger('pesanan_id')->nullable();
            $table->foreign('pesanan_id')->references('id')->on('pesanan')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'waktu_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lelang');
    }
};
