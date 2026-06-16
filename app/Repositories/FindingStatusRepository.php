<?php

namespace App\Repositories;

use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FindingStatusRepository
{
    public function upsert(
        Service $service,
        PipelineRun $pipelineRun,
        string $scanSource,
        array $tool,
        array $finding,
    ): void {
        if ($scanSource === 'github') {
            FindingStatus::where('service_id', (string) $service->_id)
                ->whereNull('scan_source')
                ->where('fingerprint', $finding['fingerprint'])
                ->update(['scan_source' => 'github']);
        }

        FindingStatus::upsert(
            [
                [
                    'service_id' => (string) $service->_id,
                    'pipeline_run_id' => (string) $pipelineRun->_id,
                    'scan_source' => $scanSource,
                    'fingerprint' => $finding['fingerprint'],
                    'tool' => $tool,
                    'severity' => $finding['severity'],
                    'type' => $finding['type'],
                    'title' => $finding['title'] ?? null,
                    'reference_id' => $finding['reference_id'],
                    'current_status' => 'open',
                    'status_updated_at' => Carbon::now()->toDateTimeString(),
                    'updated_by' => null,
                    'resolution_reason' => null,
                    'history' => [],
                ],
            ],
            ['service_id', 'scan_source', 'fingerprint'],
            ['pipeline_run_id', 'title', 'severity', 'type', 'reference_id'],
        );
    }

    /**
     * Mark findings that reappear after being resolved/closed as 'returning'.
     * Only touches resolved/closed statuses — open/snoozed/returning are left alone.
     *
     * @return string[] Jira issue keys of affected findings
     */
    public function markReturning(
        string $serviceId,
        string $scanSource,
        string $pipelineRunId,
        array $fingerprints,
    ): array {
        if ($fingerprints === []) {
            return [];
        }

        $now = Carbon::now()->toDateTimeString();
        $jiraKeys = [];

        foreach (['resolved', 'closed'] as $fromStatus) {
            $base = FindingStatus::where('service_id', $serviceId)
                ->where('current_status', $fromStatus)
                ->whereIn('fingerprint', $fingerprints);
            $this->applyScanSourceScope($base, $scanSource);

            $entry = [
                'from' => $fromStatus,
                'to' => 'returning',
                'at' => $now,
                'by' => 'system',
            ];

            /** @var FindingStatus $finding */
            foreach ((clone $base)->get() as $finding) {
                $history = is_array($finding->history) ? $finding->history : [];
                $history[] = $entry;

                $finding->history = $history;
                $finding->current_status = 'returning';
                $finding->pipeline_run_id = $pipelineRunId;
                $finding->status_updated_at = $now;
                $finding->save();

                if ($finding->jira_issue_key !== null) {
                    $jiraKeys[] = (string) $finding->jira_issue_key;
                }
            }
        }

        return $jiraKeys;
    }

    public function markSnoozed(FindingStatus $finding, string $byEmail, ?string $reason = null): bool
    {
        if (! in_array($finding->current_status, ['open', 'returning'], true)) {
            return false;
        }

        $finding->snooze_reason = ($reason !== '' ? $reason : null);
        $this->transition($finding, 'snoozed', $byEmail, $reason);

        return true;
    }

    public function markUnsnoozed(FindingStatus $finding, string $byEmail): bool
    {
        if ($finding->current_status !== 'snoozed') {
            return false;
        }

        $finding->snooze_reason = null;
        $this->transition($finding, 'open', $byEmail);

        return true;
    }

    public function markResolved(
        string $serviceId,
        string $scanSource,
        array $currentFingerprints,
    ): array {
        $now = Carbon::now()->toDateTimeString();
        $jiraKeys = [];

        foreach (['open', 'returning', 'snoozed'] as $fromStatus) {
            $base = FindingStatus::where('service_id', $serviceId)
                ->where('current_status', $fromStatus);
            $this->applyScanSourceScope($base, $scanSource);

            if ($currentFingerprints !== []) {
                $base->whereNotIn('fingerprint', $currentFingerprints);
            }

            $entry = [
                'from' => $fromStatus,
                'to' => 'resolved',
                'at' => $now,
                'by' => 'system',
            ];

            /** @var FindingStatus $finding */
            foreach ((clone $base)->get() as $finding) {
                $history = is_array($finding->history) ? $finding->history : [];
                $history[] = $entry;

                $finding->history = $history;
                $finding->current_status = 'resolved';
                $finding->status_updated_at = $now;
                $finding->save();

                if ($finding->jira_issue_key !== null) {
                    $jiraKeys[] = (string) $finding->jira_issue_key;
                }
            }
        }

        return $jiraKeys;
    }

    public function getStatus(
        string $serviceId,
        string $scanSource,
        string $fingerprint,
    ): ?string {
        $query = FindingStatus::where('service_id', $serviceId)
            ->where('fingerprint', $fingerprint);
        $this->applyScanSourceScope($query, $scanSource);

        return $query
            ->value('current_status');
    }

    /**
     * Find a finding status by service, scan source, and fingerprint.
     */
    public function findByUniqueKeys(
        string $serviceId,
        string $scanSource,
        string $fingerprint,
    ): ?FindingStatus {
        $query = FindingStatus::where('service_id', $serviceId)
            ->where('fingerprint', $fingerprint);
        $this->applyScanSourceScope($query, $scanSource);

        return $query->first();
    }

    public function findOpenDefaultRunFindings(
        string $serviceId,
        string $scanSource,
        array $fingerprints,
    ): Collection {
        if ($fingerprints === []) {
            return collect();
        }

        $query = FindingStatus::where('service_id', $serviceId)
            ->whereIn('current_status', ['open', 'returning'])
            ->whereIn('fingerprint', $fingerprints);
        $this->applyScanSourceScope($query, $scanSource);

        return $query->get([
            '_id', 'title', 'tool', 'severity', 'type',
            'reference_id', 'current_status', 'status_updated_at',
        ]);
    }

    private function applyScanSourceScope(mixed $query, string $scanSource): void
    {
        $query->where(function ($query) use ($scanSource): void {
            $query->where('scan_source', $scanSource);

            if ($scanSource === 'github') {
                $query->orWhereNull('scan_source');
            }
        });
    }

    private function transition(
        FindingStatus $finding,
        string $toStatus,
        string $byEmail,
        ?string $reason = null,
    ): void {
        $now = Carbon::now()->toDateTimeString();

        $history = is_array($finding->history) ? $finding->history : [];
        $entry = [
            'from' => $finding->current_status,
            'to' => $toStatus,
            'at' => $now,
            'by' => $byEmail,
        ];

        if ($reason !== null && $reason !== '') {
            $entry['reason'] = $reason;
        }

        $history[] = $entry;

        $finding->current_status = $toStatus;
        $finding->status_updated_at = $now;
        $finding->updated_by = $byEmail;
        $finding->history = $history;
        $finding->save();
    }
}
