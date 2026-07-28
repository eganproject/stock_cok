<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Product;
use App\Models\Stock;
use App\Models\SyncLog;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Mengisi data awal DIVISI dan GUDANG.
     *
     * Produk & stok sengaja TIDAK di-seed — keduanya akan terisi otomatis
     * oleh proses sinkronisasi API (firstOrCreate per division_id + sku).
     *
     * Katalog produk terpisah per divisi (UNIQUE division_id + sku), jadi kode
     * yang kebetulan sama di divisi berbeda tidak akan tercampur.
     */
    public function run(): void
    {
        // ---- Kosongkan data inventory lama ----
        // Urutan anak -> induk supaya aman baik di MySQL maupun MariaDB,
        // tidak bergantung pada perilaku ON DELETE CASCADE.
        Stock::query()->delete();
        Product::query()->delete();
        SyncLog::query()->delete();
        Warehouse::query()->delete();
        Division::query()->delete();

        // ---- Divisi ----
        $akrilik = Division::create([
            'code'        => 'AKR',
            'name'        => 'Divisi Aksesoris Akrilik',
            'description' => 'Produk dan aksesoris berbahan akrilik',
        ]);

        $otomotif = Division::create([
            'code'        => 'OTO',
            'name'        => 'Divisi Otomotif',
            'description' => 'Suku cadang dan perlengkapan otomotif',
        ]);

        // ---- Gudang ----
        // [divisi, kode, nama, urutan tampil, konfigurasi API tambahan]
        // Token API dibaca dari .env (JANGAN hardcode di sini — ikut ter-commit ke Git).
        $warehouses = [
            [$akrilik, 'NGW',   'Gudang Nanggewer', 1, [
                'base_url'  => 'https://inventory.cok-analytics.com',
                'auth_type' => 'bearer',
                'api_token' => env('GUDANGNGW_API_TOKEN'),
            ]],
            [$akrilik, 'GD-29', 'Gudang 29', 2, [
                'base_url'  => 'https://gudang29.com',
                'auth_type' => 'bearer',
                'api_token' => env('GUDANG29_API_TOKEN'),
            ]],
            [$akrilik, 'SBY',   'Gudang Surabaya', 3, [
                'base_url'  => 'https://gudangsurabaya.com',
                'auth_type' => 'bearer',
                'api_token' => env('GUDANGSBY_API_TOKEN'),
            ]],
            [$otomotif, 'GD-24', 'Gudang 24', 1, [
                'base_url'  => 'https://gudangseha24.com',
                'auth_type' => 'bearer',
                'api_token' => env('GUDANG24_API_TOKEN'),
            ]],
        ];

        foreach ($warehouses as [$division, $code, $name, $sequence, $apiConfig]) {
            Warehouse::create(array_merge([
                'division_id' => $division->id,
                'code'        => $code,
                'name'        => $name,
                'sequence'    => $sequence,
                'timezone'    => 'Asia/Jakarta',
                'is_active'   => true,
                'sync_status' => 'never',
            ], $apiConfig));
        }
    }
}
