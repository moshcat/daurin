<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_sampah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('jenis_sampah');
            $table->decimal('berat', 10, 2);
            $table->decimal('harga', 15, 2);
            $table->string('foto_path')->nullable();
            $table->string('ai_label')->nullable();
            $table->double('ai_confidence')->nullable();
            $table->string('status')->default('tersedia');
            $table->unsignedBigInteger('claimed_by')->nullable();
            $table->foreign('claimed_by')->references('id')->on('users')->nullOnDelete();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_sampah');
    }
};
