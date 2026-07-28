<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas Excel rapi untuk Laporan Ketimpangan Antar-Gudang: kolom stok per
 * gudang (dinamis, urut sequence), Ketimpangan, dan Saran Transfer.
 */
class ImbalanceExport
{
    private const HEADER_FILL   = '0F172A';
    private const NUMBER_FORMAT = '#,##0.###';
    private const COLOR_MENIPIS = 'D97706';
    private const COLOR_HABIS   = 'E11D48';

    /** @param array<string, mixed> $data konteks dari ReportController::prepareImbalance() */
    public function __construct(private readonly array $data)
    {
    }

    public function download(): StreamedResponse
    {
        $spreadsheet = $this->build();
        $writer = new Xlsx($spreadsheet);

        $code = $this->data['division']?->code ?? 'ketimpangan';
        $asOf = $this->data['asOfRaw'] ?: 'terkini';
        $filename = "ketimpangan-gudang_{$code}_{$asOf}_" . now()->format('Ymd_His') . '.xlsx';

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
        $rows = $this->data['sorted'];
        $division = $this->data['division'];
        $warehouses = $this->data['divisionWarehouses'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ketimpangan');

        // Header dinamis: SKU, Nama, Kategori, UOM, [gudang...], Ketimpangan, Saran Transfer
        $headers = ['SKU', 'Nama Barang', 'Kategori', 'UOM'];
        $whStartCol = count($headers) + 1;
        foreach ($warehouses as $w) {
            $headers[] = $w->name;
        }
        $imbalanceCol = count($headers) + 1;
        $headers[] = 'Ketimpangan';
        $headers[] = 'Saran Transfer';

        $lastColIdx = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($lastColIdx);
        $whCount = $warehouses->count();

        // Judul & meta
        $sheet->setCellValue('A1', 'LAPORAN KETIMPANGAN ANTAR-GUDANG');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $meta = 'Divisi: ' . ($division->name ?? '—')
            . '  |  Posisi: ' . ($this->data['asOfRaw'] ? Carbon::parse($this->data['asOfRaw'])->translatedFormat('d M Y') : 'Terkini');
        $sheet->setCellValue('A2', $meta);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $sheet->setCellValue('A3', 'Diekspor: ' . now()->translatedFormat('d M Y, H:i') . '  |  Total SKU: ' . $rows->count());
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->getFont()->setSize(10)->getColor()->setRGB('94A3B8');

        // Header
        $headerRow = 5;
        foreach ($headers as $i => $label) {
            $sheet->setCellValue([$i + 1, $headerRow], $label);
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_FILL]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Isi
        $r = $headerRow + 1;
        foreach ($rows as $row) {
            $sheet->setCellValue([1, $r], $row['sku']);
            $sheet->setCellValue([2, $r], $row['name']);
            $sheet->setCellValue([3, $r], $row['category'] ?? '—');
            $sheet->setCellValue([4, $r], $row['uom']);

            $col = $whStartCol;
            foreach ($warehouses as $w) {
                $cell = $row['per_wh'][$w->code] ?? null;
                if ($cell) {
                    $sheet->setCellValue([$col, $r], (float) $cell['qty']);
                    if ($cell['status_key'] === 'menipis') {
                        $sheet->getStyle(Coordinate::stringFromColumnIndex($col) . $r)->getFont()->getColor()->setRGB(self::COLOR_MENIPIS);
                    } elseif ($cell['status_key'] === 'habis') {
                        $sheet->getStyle(Coordinate::stringFromColumnIndex($col) . $r)->getFont()->getColor()->setRGB(self::COLOR_HABIS);
                    }
                } else {
                    $sheet->setCellValue([$col, $r], '—');
                    $sheet->getStyle(Coordinate::stringFromColumnIndex($col) . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                $col++;
            }

            $sheet->setCellValue([$imbalanceCol, $r], (float) $row['imbalance']);
            $sheet->getStyle(Coordinate::stringFromColumnIndex($imbalanceCol) . $r)->getFont()->setBold(true);

            $move = $row['move'];
            $saran = $move ? "{$move['from']} → {$move['to']}: " . $this->num($move['qty']) . " {$row['uom']}" : '—';
            $sheet->setCellValue([$imbalanceCol + 1, $r], $saran);
            $r++;
        }
        $lastRow = $r - 1;

        if ($lastRow >= $headerRow + 1) {
            // Format angka: kolom gudang + Ketimpangan.
            for ($c = $whStartCol; $c <= $imbalanceCol; $c++) {
                $letter = Coordinate::stringFromColumnIndex($c);
                $range = "{$letter}" . ($headerRow + 1) . ":{$letter}{$lastRow}";
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
        }

        for ($i = 1; $i <= $lastColIdx; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->freezePane('A' . ($headerRow + 1));

        return $spreadsheet;
    }

    private function num($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ',');
    }
}
