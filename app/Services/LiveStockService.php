<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Sync\WarehouseStockClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Menarik stok live satu gudang dari API, dinormalisasi ke baris tabel, dengan
 * cache singkat. Kunci cache SAMA dengan yang dipakai halaman Inventory sehingga
 * data dibagi pakai (tidak menembak API dua kali untuk gudang & tanggal sama).
 */
class LiveStockService
{
    private const CACHE_SECONDS = 120;

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: string}  [rows, fetchedAt]
     */
    public function warehouseRows(Warehouse $warehouse, bool $fresh = false, ?CarbonImmutable $asOf = null): array
    {
        $key = 'inventory_live_wh_' . $warehouse->id . '_asof_' . ($asOf?->format('Ymd') ?? 'now');

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addSeconds(self::CACHE_SECONDS), function () use ($warehouse, $asOf) {
            $client = new WarehouseStockClient($warehouse);
            $rows = [];

            foreach ($client->fetchAsOf($asOf) as $item) {
                if ($item->isDeleted()) {
                    continue;
                }

                $statusKey = $item->qty <= 0
                    ? 'habis'
                    : ($item->qty <= ($item->minQty ?? 0) ? 'menipis' : 'tersedia');

                $rows[] = [
                    'sku'            => $item->sku,
                    'name'           => $item->name,
                    'category'       => $item->category,
                    'uom'            => $item->uom,
                    'qty'            => $item->qty,
                    'min_qty'        => $item->minQty ?? 0,
                    'status_key'     => $statusKey,
                    'status_order'   => $statusKey === 'habis' ? 0 : ($statusKey === 'menipis' ? 1 : 2),
                    'warehouse_code' => $warehouse->code,
                    'warehouse_name' => $warehouse->name,
                    'updated'        => optional($item->sourceUpdatedAt)->setTimezone(config('app.timezone'))->format('Y-m-d H:i'),
                ];
            }

            return [$rows, now()->format('Y-m-d H:i:s')];
        });
    }
}
