<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Jejak balik: pesanan settlement berasal dari lelang mana (null untuk nego legacy).
            $table->unsignedBigInteger('lelang_id')->nullable()->after('bahan_baku_id');
            $table->foreign('lelang_id')->references('id')->on('lelang')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropForeign(['lelang_id']);
            $table->dropColumn('lelang_id');
        });
    }
};
