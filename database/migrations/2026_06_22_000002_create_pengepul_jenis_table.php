<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengepul_jenis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('jenis_sampah');
            $table->timestamps();

            $table->unique(['user_id', 'jenis_sampah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengepul_jenis');
    }
};
