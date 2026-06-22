<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Identitas perusahaan untuk user role "industri" (diisi saat registrasi
     * dari mana pun, karena alamat kantor diketik manual — bukan dari geolokasi).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nama_pt')->nullable()->after('lng');
            $table->string('alamat_pt')->nullable()->after('nama_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nama_pt', 'alamat_pt']);
        });
    }
};
