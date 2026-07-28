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
 * Berkas Excel rapi untuk Laporan Perlu Restock: judul + info filter, header
 * tebal, kolom auto-lebar, format angka, freeze, border, dan pewarnaan status
 * (menipis=amber, habis=merah).
 */
class RestockExport
{
    private const HEADER_FILL   = '0F172A';
    private const NUMBER_FORMAT = '#,##0.###';
    private const COLOR_MENIPIS = 'D97706';
    private const COLOR_HABIS   = 'E11D48';

    private const HEADERS = ['SKU', 'Nama Barang', 'Kategori', 'UOM', 'Gudang', 'Stok', 'Min', 'Kekurangan', 'Status'];
    private const NUMERIC_COLS = [6, 7, 8]; // Stok, Min, Kekurangan

    /** @param array<string, mixed> $data konteks dari ReportController::prepare() */
    public function __construct(private readonly array $data)
    {
    }

    public function download(): StreamedResponse
    {
        $spreadsheet = $this->build();
        $writer = new Xlsx($spreadsheet);

        $code = $this->data['division']?->code ?? 'restock';
        $asOf = $this->data['asOfRaw'] ?: 'terkini';
        $filename = "perlu-restock_{$code}_{$asOf}_" . now()->format('Ymd_His') . '.xlsx';

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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Perlu Restock');

        $lastColIdx = count(self::HEADERS);
        $lastCol = Coordinate::stringFromColumnIndex($lastColIdx);

        // Judul & meta
        $sheet->setCellValue('A1', 'LAPORAN PERLU RESTOCK');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $selected = $this->data['selectedWarehouse'];
        $meta = 'Divisi: ' . ($division->name ?? '—')
            . '  |  Gudang: ' . ($selected->name ?? 'Semua gudang divisi')
            . '  |  Posisi: ' . ($this->data['asOfRaw'] ? Carbon::parse($this->data['asOfRaw'])->translatedFormat('d M Y') : 'Terkini');
        $sheet->setCellValue('A2', $meta);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $sheet->setCellValue('A3', 'Diekspor: ' . now()->translatedFormat('d M Y, H:i') . '  |  Total baris: ' . $rows->count());
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->getFont()->setSize(10)->getColor()->setRGB('94A3B8');

        // Header
        $headerRow = 5;
        foreach (self::HEADERS as $i => $label) {
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
            $sheet->setCellValue([5, $r], $row['warehouse_name']);
            $sheet->setCellValue([6, $r], (float) $row['qty']);
            $sheet->setCellValue([7, $r], (float) $row['min_qty']);
            $sheet->setCellValue([8, $r], (float) $row['shortfall']);
            $sheet->setCellValue([9, $r], $row['status_key'] === 'habis' ? 'Habis' : 'Menipis');

            // Warna stok sesuai status.
            $color = $row['status_key'] === 'habis' ? self::COLOR_HABIS : self::COLOR_MENIPIS;
            $sheet->getStyle('F' . $r)->getFont()->setBold(true)->getColor()->setRGB($color);
            $sheet->getStyle('I' . $r)->getFont()->getColor()->setRGB($color);
            $r++;
        }
        $lastRow = $r - 1;

        if ($lastRow >= $headerRow + 1) {
            foreach (self::NUMERIC_COLS as $colIdx) {
                $c = Coordinate::stringFromColumnIndex($colIdx);
                $range = "{$c}" . ($headerRow + 1) . ":{$c}{$lastRow}";
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
}
