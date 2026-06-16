<?php

namespace App\Exports;

use App\Repositories\ActivityLogRepository;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Activitylog\Models\Activity;

class AuditLogExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly ActivityLogRepository $repository,
        private readonly string $log,
        private readonly string $event,
        private readonly string $actor,
        private readonly string $from,
        private readonly string $to,
    ) {}

    /** @return Builder<Activity> */
    public function query()
    {
        return $this->repository->getActivityLogs(
            log: $this->log,
            actor: $this->actor,
            from: $this->from,
            to: $this->to,
            event: $this->event,
        );
    }

    public function headings(): array
    {
        return ['Tijdstip (UTC)', 'Log', 'Event', 'Omschrijving', 'Actor', 'Details'];
    }

    public function map($row): array
    {
        if ($row->causer) {
            $actor = $row->causer->name;
        } elseif ($row->properties->get('email')) {
            $actor = $row->properties->get('email');
        } else {
            $actor = '—';
        }

        $details = $row->properties->map(
            fn ($value, $key): string => is_array($value)
                ? "{$key}: ".json_encode($value)
                : "{$key}: {$value}"
        )->implode(', ');

        return [
            $row->created_at->format('d-m-Y H:i').' UTC',
            $row->log_name,
            $row->event,
            $row->description,
            $actor,
            $details,
        ];
    }

    public function styles(Worksheet $_sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e293b'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 19,
            'B' => 10,
            'C' => 20,
            'D' => 50,
            'E' => 41,
            'F' => 40,
        ];
    }
}
