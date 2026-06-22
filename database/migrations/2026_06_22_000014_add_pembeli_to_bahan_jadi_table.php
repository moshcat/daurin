<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan_jadi', function (Blueprint $table) {
            $table->foreignId('pembeli_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('dibayar_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bahan_jadi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pembeli_id');
            $table->dropColumn('dibayar_at');
        });
    }
};
