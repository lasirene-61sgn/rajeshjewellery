<?php

namespace App\Exports;

use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkOrdersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    /**
     * @param \Illuminate\Support\Enumerable $orders
     */
    public function __construct(protected Enumerable $orders)
    {
    }

    public function collection(): Enumerable
    {
        return $this->orders;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Reference No',
            'Order No',
            'Design Code',
            'Weight',
            'Length',
            'Qty',
        ];
    }

    /**
     * @param mixed $row
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        return [
            $row->reference_no ?? '-',
            $row->work_order_no ?? '-',
            $row->design_code ?? '-',
            $row->target_weight !== null ? (float) $row->target_weight : null,
            $row->length ?: ($row->size ?: '-'),
            $row->quantity ?? null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => '#,##0.000',
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array<int|string, array<string, mixed>>|null
     */
    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '0F172A'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
