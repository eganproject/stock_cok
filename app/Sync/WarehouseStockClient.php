<?php

namespace App\Sync;

use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Generator;

/**
 * Mengambil data stok dari API sebuah gudang sesuai kontrak
 * (docs/api-kontrak-stok-gudang.md): amplop success/meta/data, pagination,
 * dan filter updated_since.
 *
 * Memakai Warehouse::apiRequest() sehingga base URL + auth + timeout konsisten
 * dengan fitur Test Koneksi.
 */
class WarehouseStockClient
{
    private ?CarbonImmutable $serverTime = null;

    public function __construct(private readonly Warehouse $warehouse)
    {
    }

    /**
     * Waktu server gudang dari halaman pertama — dipakai sebagai penanda
     * (watermark) untuk sinkronisasi berikutnya. null bila belum fetch.
     */
    public function serverTime(): ?CarbonImmutable
    {
        return $this->serverTime;
    }

    /**
     * Menarik seluruh halaman dan menghasilkan tiap baris sebagai StockItemData.
     *
     * @return Generator<int, StockItemData>
     *
     * @throws SyncException
     */
    public function fetch(?CarbonInterface $since = null, int $perPage = 100): Generator
    {
        $page = 1;
        $totalPages = 1;

        do {
            $response = $this->warehouse->apiRequest(30)->get('/api/v1/stocks', array_filter([
                'updated_since' => $since?->toIso8601String(),
                'page'          => $page,
                'per_page'      => $perPage,
            ], fn ($v) => $v !== null));

            if (! $response->successful()) {
                throw new SyncException(
                    "Gagal mengambil stok (HTTP {$response->status()}) pada halaman {$page}."
                    . ($response->status() === 401 ? ' Token kemungkinan salah.' : '')
                );
            }

            $body = $response->json();
            if (! is_array($body) || ! ($body['success'] ?? false)) {
                throw new SyncException("Respons bukan format kontrak (field \"success\" tidak true) pada halaman {$page}.");
            }

            $meta = $body['meta'] ?? [];

            $remoteCode = $meta['warehouse_code'] ?? null;
            if ($remoteCode !== null && $remoteCode !== $this->warehouse->code) {
                throw new SyncException(
                    "Kode gudang dari API (\"{$remoteCode}\") berbeda dengan sistem (\"{$this->warehouse->code}\"). "
                    . 'URL kemungkinan tertukar dengan gudang lain.'
                );
            }

            if ($page === 1 && ! empty($meta['server_time'])) {
                $this->serverTime = CarbonImmutable::parse($meta['server_time']);
            }

            $rows = $body['data'] ?? [];
            if (! is_array($rows)) {
                throw new SyncException("Field \"data\" bukan array pada halaman {$page}.");
            }

            foreach ($rows as $i => $row) {
                if (! is_array($row)) {
                    throw new SyncException("Baris ke-{$i} bukan objek pada halaman {$page}.");
                }
                yield StockItemData::fromApi($row);
            }

            $totalPages = max(1, (int) ($meta['total_pages'] ?? 1));
            $page++;
        } while ($page <= $totalPages);
    }
}
