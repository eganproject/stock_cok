<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Mengatur urutan tampil kolom gudang di halaman Inventory.
            // Makin kecil, makin di depan/kiri.
            $table->unsignedInteger('sequence')->default(0)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });
    }
};
