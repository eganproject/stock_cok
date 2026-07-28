<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membangun berkas Excel (.xlsx) rapi dari hasil halaman Inventory:
 * judul, info filter, header tebal berwarna, kolom auto-lebar, format angka,
 * dan pewarnaan status (menipis/habis) mengikuti tampilan layar.
 */
class InventoryExport
{
    private const HEADER_FILL   = '0F172A'; // slate-900
    private const NUMBER_FORMAT = '#,##0.###';
    private const COLOR_MENIPIS = 'D97706'; // amber-600
    private const COLOR_HABIS   = 'E11D48'; // rose-600

    private const STATUS_LABEL = [
        'tersedia' => 'Tersedia',
        'menipis'  => 'Menipis',
        'habis'    => 'Habis',
    ];

    /** @param array<string, mixed> $data konteks dari InventoryController::prepare() */
    public function __construct(private readonly array $data)
    {
    }

    public function download(): StreamedResponse
    {
        $spreadsheet = $this->build();
        $writer = new Xlsx($spreadsheet);

        $division = $this->data['division'];
        $code = $division?->code ?? 'inventory';
        $asOf = $this->data['asOfRaw'] ?: 'terkini';
        $filename = "inventory_{$code}_{$asOf}_" . now()->format('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0, no-store',
        ]);
    }

    private function build(): Spreadsheet
    {
        $grouped   = (bool) $this->data['grouped'];
        $division  = $this->data['division'];
        $warehouses = $this->data['divisionWarehouses'];
        $rows      = $this->data['sorted'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventory');

        [$headers, $numericCols] = $this->columns($grouped, $warehouses);
        $lastColIdx = count($headers);
        $lastCol = $this->colLetter($lastColIdx);

        // --- Judul & meta ---
        $sheet->setCellValue('A1', 'LAPORAN INVENTORY');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $meta = 'Divisi: ' . ($division->name ?? '—')
            . '  |  Cakupan: ' . ($grouped ? 'Semua gudang (gabungan per SKU)' : ('Gudang ' . ($this->data['selectedWarehouse']->name ?? '-')))
            . '  |  Posisi stok: ' . ($this->data['asOfRaw'] ? Carbon::parse($this->data['asOfRaw'])->translatedFormat('d M Y') : 'Terkini');
        $sheet->setCellValue('A2', $meta);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $sheet->setCellValue('A3', 'Diekspor: ' . now()->translatedFormat('d M Y, H:i') . '  |  Total baris: ' . $rows->count());
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->getFont()->setSize(10)->getColor()->setRGB('94A3B8');

        // --- Header tabel ---
        $headerRow = 5;
        foreach ($headers as $i => $label) {
            $this->put($sheet,$i + 1, $headerRow, $label);
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_FILL]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // --- Isi ---
        $r = $headerRow + 1;
        foreach ($rows as $row) {
            $grouped ? $this->writeGroupedRow($sheet, $r, $row, $warehouses) : $this->writeSingleRow($sheet, $r, $row);
            $r++;
        }
        $lastRow = $r - 1;

        // --- Format angka + border + auto-size ---
        if ($lastRow >= $headerRow + 1) {
            foreach ($numericCols as $colIdx) {
                $c = $this->colLetter($colIdx);
                $sheet->getStyle("{$c}" . ($headerRow + 1) . ":{$c}{$lastRow}")
                    ->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
                $sheet->getStyle("{$c}" . ($headerRow + 1) . ":{$c}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
        }

        for ($i = 1; $i <= $lastColIdx; $i++) {
            $sheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }

        // Bekukan judul + header agar tetap terlihat saat scroll.
        $sheet->freezePane('A' . ($headerRow + 1));

        return $spreadsheet;
    }

    /**
     * Definisi kolom + indeks kolom numerik (untuk format angka).
     *
     * @return array{0: list<string>, 1: list<int>}
     */
    private function columns(bool $grouped, $warehouses): array
    {
        if ($grouped) {
            $headers = ['SKU', 'Nama Barang', 'Kategori', 'UOM'];
            $numeric = [];
            $col = count($headers);
            foreach ($warehouses as $w) {
                $headers[] = $w->name;
                $numeric[] = ++$col;
            }
            $headers[] = 'Total Stok';
            $numeric[] = ++$col;
            $headers[] = 'Status';
            $col++;
            $headers[] = 'Update Terakhir';

            return [$headers, $numeric];
        }

        $headers = ['SKU', 'Nama Barang', 'Kategori', 'UOM', 'Gudang', 'Stok', 'Min', 'Status', 'Update Terakhir'];

        return [$headers, [6, 7]];
    }

    /** @param array<string,mixed> $row */
    private function writeGroupedRow($sheet, int $r, array $row, $warehouses): void
    {
        $col = 1;
        $this->put($sheet,$col++, $r, $row['sku']);
        $this->put($sheet,$col++, $r, $row['name']);
        $this->put($sheet,$col++, $r, $row['category'] ?? '—');
        $this->put($sheet,$col++, $r, $row['uom']);

        foreach ($warehouses as $w) {
            $cell = $row['per_wh'][$w->code] ?? null;
            if ($cell) {
                $this->put($sheet,$col, $r, (float) $cell['qty']);
                $this->colorByStatus($sheet, $this->colLetter($col) . $r, $cell['status_key']);
            } else {
                $this->put($sheet,$col, $r, '—');
                $sheet->getStyle($this->colLetter($col) . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            $col++;
        }

        $this->put($sheet,$col, $r, (float) $row['qty']);
        $this->colorByStatus($sheet, $this->colLetter($col) . $r, $row['status_key'], true);
        $col++;

        $this->put($sheet,$col++, $r, self::STATUS_LABEL[$row['status_key']] ?? $row['status_key']);
        $this->put($sheet,$col, $r, $this->fmtDate($row['updated']));
    }

    /** @param array<string,mixed> $row */
    private function writeSingleRow($sheet, int $r, array $row): void
    {
        $col = 1;
        $this->put($sheet,$col++, $r, $row['sku']);
        $this->put($sheet,$col++, $r, $row['name']);
        $this->put($sheet,$col++, $r, $row['category'] ?? '—');
        $this->put($sheet,$col++, $r, $row['uom']);
        $this->put($sheet,$col++, $r, $row['warehouse_name'] ?? '—');
        $this->put($sheet,$col, $r, (float) $row['qty']);
        $this->colorByStatus($sheet, $this->colLetter($col) . $r, $row['status_key']);
        $col++;
        $this->put($sheet,$col++, $r, (float) $row['min_qty']);
        $this->put($sheet,$col++, $r, self::STATUS_LABEL[$row['status_key']] ?? $row['status_key']);
        $this->put($sheet,$col, $r, $this->fmtDate($row['updated']));
    }

    private function colorByStatus($sheet, string $cell, string $status, bool $bold = false): void
    {
        $font = $sheet->getStyle($cell)->getFont();
        if ($status === 'menipis') {
            $font->getColor()->setRGB(self::COLOR_MENIPIS);
        } elseif ($status === 'habis') {
            $font->getColor()->setRGB(self::COLOR_HABIS);
        }
        if ($bold) {
            $font->setBold(true);
        }
    }

    private function fmtDate(?string $value): string
    {
        return $value ? Carbon::parse($value)->translatedFormat('d M Y, H:i') : '—';
    }

    private function colLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }

    /** Set nilai sel via koordinat [kolom, baris] (1-based) — pengganti API lama. */
    private function put($sheet, int $col, int $row, mixed $value): void
    {
        $sheet->setCellValue([$col, $row], $value);
    }
}
