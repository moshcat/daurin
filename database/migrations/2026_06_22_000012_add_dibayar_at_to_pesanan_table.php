<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Waktu pembayaran (simulasi). Null = belum dibayar.
            $table->timestamp('dibayar_at')->nullable()->after('harga_sepakat');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn('dibayar_at');
        });
    }
};
