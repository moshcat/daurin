<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_jadi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_pesanan_id')->nullable();
            $table->foreign('source_pesanan_id')->references('id')->on('pesanan')->nullOnDelete();
            $table->string('nama');
            $table->string('jenis_sampah');
            $table->string('deskripsi')->nullable();
            $table->decimal('berat', 10, 2);
            $table->decimal('harga', 15, 2);
            $table->string('foto_path')->nullable();
            $table->string('status')->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_jadi');
    }
};
