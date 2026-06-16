<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogExport;
use App\Repositories\ActivityLogRepository;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogExportController extends Controller
{
    public function __invoke(
        Request $request,
        ActivityLogRepository $repository,
    ): BinaryFileResponse {
        $this->authorize('view.logs');

        $validated = $request->validate([
            'log' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:255'],
            'actor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $export = new AuditLogExport(
            repository: $repository,
            log: $validated['log'] ?? '',
            event: $validated['event'] ?? '',
            actor: (string) ($validated['actor'] ?? ''),
            from: $validated['from'] ?? '',
            to: $validated['to'] ?? '',
        );

        $filename = 'auditlog-'.now()->format('Y-m-d_H-i-s').'.xlsx';

        activity()
            ->useLog('audit')
            ->causedBy(auth()->user())
            ->event('audit_log_exported')
            ->withProperties(array_filter([
                'log' => $validated['log'] ?? null,
                'event' => $validated['event'] ?? null,
                'actor' => $validated['actor'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]))
            ->log('Audit log exported');

        return Excel::download($export, $filename);
    }
}
