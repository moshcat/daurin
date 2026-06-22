<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_baku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_listing_id')->nullable();
            $table->foreign('source_listing_id')->references('id')->on('listing_sampah')->nullOnDelete();
            $table->string('jenis_sampah');
            $table->string('peruntukan')->nullable();
            $table->decimal('berat', 10, 2);
            $table->decimal('harga_awal', 15, 2);
            $table->string('status')->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_baku');
    }
};
