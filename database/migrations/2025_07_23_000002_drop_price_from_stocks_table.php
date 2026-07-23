<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sistem tidak memerlukan informasi harga, sehingga kolom ini dilepas.
 * Konsekuensinya harga juga dihapus dari kontrak API — gudang tidak perlu
 * membuka data harga ke sistem pusat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->nullable()->after('min_qty');
        });
    }
};
