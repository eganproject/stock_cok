<?php

namespace App\Sync;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Satu baris stok dari API gudang, sudah tervalidasi & ternormalisasi.
 *
 * Semua pengecekan format kontrak dipusatkan di sini, sehingga bagian lain
 * (client, service) bisa bekerja dengan data yang dijamin bersih.
 */
final class StockItemData
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $category,
        public readonly string $uom,
        public readonly float $qty,
        public readonly ?int $minQty,
        public readonly string $status,
        public readonly CarbonImmutable $sourceUpdatedAt,
    ) {
    }

    /**
     * @throws InvalidArgumentException bila baris tidak sesuai kontrak.
     */
    public static function fromApi(array $row): self
    {
        foreach (['sku', 'name', 'uom', 'qty', 'status', 'updated_at'] as $field) {
            if (! array_key_exists($field, $row)) {
                throw new InvalidArgumentException("Field wajib \"{$field}\" tidak ada.");
            }
        }

        $sku = trim((string) $row['sku']);
        if ($sku === '') {
            throw new InvalidArgumentException('SKU kosong.');
        }

        if (! is_numeric($row['qty'])) {
            throw new InvalidArgumentException("qty bukan angka pada SKU {$sku}.");
        }
        $qty = (float) $row['qty'];
        if ($qty < 0) {
            throw new InvalidArgumentException("qty negatif pada SKU {$sku}.");
        }

        $minQty = $row['min_qty'] ?? null;
        if ($minQty !== null) {
            if (! is_numeric($minQty)) {
                throw new InvalidArgumentException("min_qty bukan angka pada SKU {$sku}.");
            }
            $minQty = (int) round((float) $minQty);
        }

        $status = (string) $row['status'];
        if (! in_array($status, ['active', 'inactive', 'deleted'], true)) {
            throw new InvalidArgumentException("status \"{$status}\" tidak sah pada SKU {$sku}.");
        }

        $updatedAt = (string) $row['updated_at'];
        if (! self::hasTimezoneOffset($updatedAt)) {
            throw new InvalidArgumentException("updated_at tanpa offset zona waktu pada SKU {$sku}: {$updatedAt}");
        }

        try {
            // Simpan sebagai instan absolut; konversi tampilan ke WIB terjadi di UI.
            $parsed = CarbonImmutable::parse($updatedAt);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException("updated_at tidak dapat diparse pada SKU {$sku}: {$updatedAt}");
        }

        $category = $row['category'] ?? null;
        $category = ($category === null || trim((string) $category) === '') ? null : (string) $category;

        return new self(
            sku: $sku,
            name: (string) $row['name'],
            category: $category,
            uom: trim((string) $row['uom']) ?: 'pcs',
            qty: $qty,
            minQty: $minQty,
            status: $status,
            sourceUpdatedAt: $parsed,
        );
    }

    public function isDeleted(): bool
    {
        return $this->status === 'deleted';
    }

    /** Cek keberadaan offset ISO-8601: +07:00 / -05:00 / Z di akhir. */
    private static function hasTimezoneOffset(string $value): bool
    {
        return (bool) preg_match('/(Z|[+-]\d{2}:?\d{2})$/', trim($value));
    }
}
