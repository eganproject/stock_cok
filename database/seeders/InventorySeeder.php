<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Data awal contoh. Struktur mengikuti desain final: katalog produk
     * TERPISAH per divisi (UNIQUE division_id + sku), sehingga kode yang
     * kebetulan sama di divisi berbeda tidak akan tercampur.
     */
    public function run(): void
    {
        $ops = Division::updateOrCreate(
            ['code' => 'OPS'],
            ['name' => 'Divisi Operasional', 'description' => 'Kebutuhan operasional kantor & distribusi']
        );

        $tek = Division::updateOrCreate(
            ['code' => 'TEK'],
            ['name' => 'Divisi Teknik', 'description' => 'Sparepart dan perlengkapan teknik']
        );

        // ---- Gudang: 3 gudang divisi OPS, 1 gudang divisi TEK ----
        $warehouses = [
            ['OPS', 'JKT-01', 'Gudang Pusat Jakarta', 'Jl. Raya Bekasi KM 21, Jakarta Timur', 25000],
            ['OPS', 'BDG-02', 'Gudang Bandung',       'Jl. Soekarno Hatta 455, Bandung',      15000],
            ['OPS', 'SBY-03', 'Gudang Surabaya',      'Jl. Rungkut Industri 12, Surabaya',    18000],
            ['TEK', 'MDN-04', 'Gudang Teknik Medan',  'Jl. Gatot Subroto 88, Medan',          12000],
        ];

        $wh = [];
        foreach ($warehouses as [$div, $code, $name, $address, $capacity]) {
            $wh[$code] = Warehouse::updateOrCreate(
                ['code' => $code],
                [
                    'division_id' => $div === 'OPS' ? $ops->id : $tek->id,
                    'name'        => $name,
                    'address'     => $address,
                    'capacity'    => $capacity,
                    'timezone'    => 'Asia/Jakarta',
                    'is_active'   => true,
                    'sync_status' => 'never',
                ]
            );
        }

        // ---- Katalog divisi OPS ----
        // [sku, nama, kategori, uom, min_qty, [qty JKT-01, BDG-02, SBY-03]]
        $opsCatalog = [
            ['SKU-1001', 'Kertas A4 80gsm',        'ATK',        'rim',  50,  [12, 80, 45]],
            ['SKU-1002', 'Laptop Dell Latitude',   'Elektronik', 'unit', 10,  [34, 12, null]],
            ['SKU-2044', 'Tinta Printer Hitam',    'ATK',        'pcs',  30,  [60, 5, 22]],
            ['SKU-2100', 'Monitor LED 24"',        'Elektronik', 'unit', 15,  [61, 40, 18]],
            ['SKU-2250', 'Pulpen Gel Hitam',       'ATK',        'pcs',  200, [890, 320, 410]],
            ['SKU-4102', 'Sarung Tangan Nitril',   'APD',        'box',  40,  [95, 9, null]],
            ['SKU-4300', 'Masker N95',             'APD',        'box',  100, [520, 210, 180]],
            ['SKU-4450', 'Sepatu Safety',          'APD',        'pair', 20,  [48, 27, 15]],
            ['SKU-5510', 'Lakban Coklat 2 inch',   'Kemasan',    'roll', 60,  [22, 140, 95]],
            ['SKU-5600', 'Kardus Box 40x40',       'Kemasan',    'pcs',  200, [780, 260, 340]],
            ['SKU-5720', 'Plastik Wrap',           'Kemasan',    'roll', 50,  [110, 6, 70]],
            ['SKU-6001', 'Air Mineral 600ml',      'Konsumsi',   'box',  120, [0, 150, 210]],
            ['SKU-6120', 'Kopi Sachet',            'Konsumsi',   'box',  100, [340, 180, null]],
            ['SKU-6300', 'Teh Celup',              'Konsumsi',   'box',  150, [410, 95, 160]],
            ['SKU-7010', 'Keyboard Wireless',      'Elektronik', 'unit', 20,  [null, 33, 47]],
            ['SKU-7200', 'Mouse Optik',            'Elektronik', 'unit', 25,  [3, 55, 62]],
            ['SKU-7350', 'Kabel LAN 10m',          'Elektronik', 'roll', 40,  [70, null, 0]],
        ];

        foreach ($opsCatalog as [$sku, $name, $cat, $uom, $min, $qtys]) {
            $product = Product::updateOrCreate(
                ['division_id' => $ops->id, 'sku' => $sku],
                ['name' => $name, 'category' => $cat, 'uom' => $uom, 'min_qty' => $min]
            );

            foreach (['JKT-01', 'BDG-02', 'SBY-03'] as $i => $code) {
                if ($qtys[$i] === null) {
                    continue;
                }
                $this->putStock($wh[$code], $product, $qtys[$i], $min, $i);
            }
        }

        // ---- Katalog divisi TEK (barang berbeda total, kode berbeda) ----
        // [sku, nama, kategori, uom, min_qty, qty]
        $tekCatalog = [
            ['TK-2001', 'Bearing 6204 2RS',        'Bearing',   'pcs',   40,  120],
            ['TK-2002', 'Bearing 6205 ZZ',         'Bearing',   'pcs',   40,  18],
            ['TK-3110', 'Oli Hidrolik ISO 68',     'Pelumas',   'liter', 100, 247.5],
            ['TK-3120', 'Oli Mesin 15W-40',        'Pelumas',   'drum',  5,   3],
            ['TK-4200', 'V-Belt B-75',             'Transmisi', 'pcs',   25,  0],
            ['TK-4210', 'Rantai Roller RS60',      'Transmisi', 'meter', 30,  85],
            ['TK-5300', 'Filter Udara Excavator',  'Filter',    'pcs',   15,  9],
            ['TK-5310', 'Filter Oli Excavator',    'Filter',    'pcs',   15,  40],
            ['TK-6400', 'Seal Kit Silinder',       'Seal',      'set',   10,  6],
            ['TK-7500', 'Baut HTB M16 x 60',       'Fastener',  'pcs',   500, 2400],
        ];

        foreach ($tekCatalog as $i => [$sku, $name, $cat, $uom, $min, $qty]) {
            $product = Product::updateOrCreate(
                ['division_id' => $tek->id, 'sku' => $sku],
                ['name' => $name, 'category' => $cat, 'uom' => $uom, 'min_qty' => $min]
            );

            $this->putStock($wh['MDN-04'], $product, $qty, $min, $i);
        }
    }

    private function putStock(Warehouse $warehouse, Product $product, $qty, int $min, int $offset): void
    {
        Stock::updateOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
            [
                'qty'               => $qty,
                'min_qty'           => $min,
                'status'            => 'active',
                'source_updated_at' => now()->subDays(($product->id + $offset) % 30)->subHours($offset * 3),
                'synced_at'         => now(),
            ]
        );
    }
}
