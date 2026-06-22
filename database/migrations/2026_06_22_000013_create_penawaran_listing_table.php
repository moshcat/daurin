<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penawaran_listing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_sampah_id')->constrained('listing_sampah')->cascadeOnDelete();
            $table->foreignId('pengepul_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('harga', 15, 2);
            $table->string('status')->default('diajukan');
            $table->timestamps();

            $table->index(['listing_sampah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_listing');
    }
};
