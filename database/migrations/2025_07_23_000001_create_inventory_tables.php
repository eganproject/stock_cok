<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->unsignedBigInteger('capacity')->nullable()->comment('Kapasitas maksimum dalam unit');

            // Konfigurasi integrasi API
            $table->string('base_url')->nullable();
            $table->string('auth_type')->default('bearer');
            $table->text('api_token')->nullable()->comment('Disimpan terenkripsi');
            $table->string('timezone')->default('Asia/Jakarta');

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('never')->comment('never|success|failed|running');
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('uom')->default('pcs');
            $table->unsignedBigInteger('min_qty')->default(0)->comment('Batas minimum versi pusat');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Kunci anti-tabrakan: SKU hanya unik di dalam satu divisi
            $table->unique(['division_id', 'sku']);
            $table->index('category');
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 15, 3)->default(0);
            $table->unsignedBigInteger('min_qty')->default(0)->comment('Batas minimum versi gudang');
            $table->unsignedBigInteger('price')->nullable()->comment('Rupiah penuh');
            $table->string('status')->default('active')->comment('active|inactive|deleted');
            $table->timestamp('source_updated_at')->nullable()->comment('updated_at dari API gudang');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
            $table->index('qty');
            $table->index('source_updated_at');
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status')->default('running')->comment('running|success|failed');
            $table->unsignedInteger('records_processed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('divisions');
    }
};
