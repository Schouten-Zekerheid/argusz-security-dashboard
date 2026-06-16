<?php

namespace App\Services;

use App\Enums\Severity;
use App\Models\FindingStatus;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use MongoDB\BSON\UTCDateTime;

class TrendAnalysisService
{
    private const array ACTIVE_FINDING_STATUSES = ['open', 'returning'];

    public const DEFAULT_CRITICAL_SLA = 7;

    public const DEFAULT_HIGH_SLA = 30;

    public const DEFAULT_MEDIUM_SLA = 90;

    public const DEFAULT_LOW_SLA = 180;

    public function getOverallSecurityScore(string $scanType = 'all'): int
    {
        $counts = collect(FindingStatus::raw(fn ($col) => $col->aggregate([
            ['$match' => $this->findingMatch([
                'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
            ], $scanType)],
            ['$group' => ['_id' => '$severity', 'count' => ['$sum' => 1]]],
        ])));

        $bySeverity = Severity::zeroCounts();
        foreach ($counts as $item) {
            $data = $this->decode($item);
            $severity = Severity::fromValue($data['_id'] ?? null);
            if (array_key_exists($severity->value, $bySeverity)) {
                $bySeverity[$severity->value] = (int) ($data['count'] ?? 0);
            }
        }

        $criticalSla = config('sla.critical');

        $slaBreachesQuery = FindingStatus::whereIn('current_status', self::ACTIVE_FINDING_STATUSES)
            ->where('severity', Severity::Critical->value)
            ->where('created_at', '<', Carbon::now()->subDays($criticalSla));
        $this->applyScanTypeScope($slaBreachesQuery, $scanType);
        $slaBreaches = $slaBreachesQuery->count();

        $deductions = $this->riskScoreDeduction($bySeverity) + ($slaBreaches * 5);

        return max(0, 100 - $deductions);
    }

    public function getKpiSummary(array $mttrByMonth, array $findingsVelocity, string $scanType = 'all'): array
    {
        $totalOpenQuery = FindingStatus::whereIn('current_status', self::ACTIVE_FINDING_STATUSES);
        $this->applyScanTypeScope($totalOpenQuery, $scanType);
        $totalOpen = $totalOpenQuery->count();

        $criticalSla = config('sla.critical');

        $slaBreachesQuery = FindingStatus::whereIn('current_status', self::ACTIVE_FINDING_STATUSES)
            ->where('severity', Severity::Critical->value)
            ->where('created_at', '<', Carbon::now()->subDays($criticalSla));
        $this->applyScanTypeScope($slaBreachesQuery, $scanType);
        $slaBreaches = $slaBreachesQuery->count();

        $avgMttr = round((float) collect($mttrByMonth)->avg('avg_days'), 1);

        $velocity = collect($findingsVelocity)->take(-2)->values();
        $trendPct = 0.0;
        if ($velocity->count() === 2 && $velocity[0]['new'] > 0) {
            $trendPct = round(
                ($velocity[1]['new'] - $velocity[0]['new']) / $velocity[0]['new'] * 100,
                1
            );
        }

        return [
            'total_open' => $totalOpen,
            'sla_breaches' => $slaBreaches,
            'avg_mttr_days' => $avgMttr,
            'trend_pct' => $trendPct,
            'trend_direction' => $trendPct > 0 ? 'up' : ($trendPct < 0 ? 'down' : 'neutral'),
        ];
    }

    public function getRiskScoreByRepository(string $scanType = 'all'): array
    {
        $results = collect(FindingStatus::raw(fn ($col) => $col->aggregate([
            ['$match' => $this->findingMatch([
                'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
            ], $scanType)],
            [
                '$group' => [
                    '_id' => ['service_id' => '$service_id', 'severity' => '$severity'],
                    'count' => ['$sum' => 1],
                ],
            ],
        ])));

        $byService = [];
        foreach ($results as $item) {
            $data = $this->decode($item);
            $serviceId = (string) ($data['_id']['service_id'] ?? '');
            $severity = Severity::fromValue($data['_id']['severity'] ?? null);
            $count = (int) ($data['count'] ?? 0);

            if (! isset($byService[$serviceId])) {
                $byService[$serviceId] = Severity::zeroCounts();
            }

            if (array_key_exists($severity->value, $byService[$serviceId])) {
                $byService[$serviceId][$severity->value] += $count;
            }
        }

        $services = $this->resolveServices(array_keys($byService));

        $scored = collect($byService)
            ->map(function (array $counts, string $serviceId) use ($services): array {
                $score = $this->riskScoreDeduction($counts);
                $service = $services->get($serviceId);

                return [
                    'repository' => $service instanceof Service ? $service->name : 'Unknown',
                    'score' => $score,
                    'critical' => $counts['CRITICAL'],
                    'high' => $counts['HIGH'],
                    'medium' => $counts['MEDIUM'],
                    'low' => $counts['LOW'],
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->take(8)
            ->all();

        $max = collect($scored)->max('score');
        $max = $max !== null && $max !== 0 ? $max : 1;

        return collect($scored)
            ->map(fn (array $item): array => array_merge(
                $item,
                ['pct' => round($item['score'] / $max * 100)]
            ))
            ->all();
    }

    /**
     * @param  'day'|'week'|'month'  $granularity
     */
    public function getFindingsVelocity(string $granularity = 'month', string $scanType = 'all'): array
    {
        $granularity = in_array($granularity, ['day', 'week', 'month'], true)
            ? $granularity
            : 'month';

        [$rangeStart, $rangeEnd] = $this->velocityInclusiveRange($granularity);
        $sinceMs = new UTCDateTime($rangeStart->getTimestamp() * 1000);

        $newKeyed = $this->keyVelocityCountsFromAggregate(
            FindingStatus::raw(fn ($col) => $col->aggregate([
                ['$match' => $this->findingMatch(['created_at' => ['$gte' => $sinceMs]], $scanType)],
                [
                    '$group' => [
                        '_id' => $this->mongoVelocityGroupId('$created_at', $granularity),
                        'count' => ['$sum' => 1],
                    ],
                ],
                ['$sort' => $this->mongoVelocitySortStage($granularity)],
            ])),
            $granularity,
        );

        $resolvedNormalized = '_resolved_bucket_at';

        $resolvedKeyed = $this->keyVelocityCountsFromAggregate(
            FindingStatus::raw(fn ($col) => $col->aggregate([
                ['$match' => $this->findingMatch([
                    'current_status' => ['$in' => ['resolved', 'closed']],
                ], $scanType)],
                [
                    '$addFields' => [
                        $resolvedNormalized => $this->aggregationCoerceMongoDate('status_updated_at'),
                    ],
                ],
                [
                    '$match' => [
                        $resolvedNormalized => [
                            '$gte' => $sinceMs,
                            '$ne' => null,
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id' => $this->mongoVelocityGroupId('$'.$resolvedNormalized, $granularity),
                        'count' => ['$sum' => 1],
                    ],
                ],
                ['$sort' => $this->mongoVelocitySortStage($granularity)],
            ])),
            $granularity,
        );

        return $this->densifyVelocityBuckets(
            $newKeyed,
            $resolvedKeyed,
            $rangeStart,
            $rangeEnd,
            $granularity,
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function velocityInclusiveRange(string $granularity): array
    {
        return match ($granularity) {
            'day' => [
                Carbon::now()->subDays(29)->startOfDay(),
                Carbon::now()->endOfDay(),
            ],
            'week' => [
                Carbon::now()->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(12),
                Carbon::now()->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            default => [
                Carbon::now()->subMonths(6)->startOfMonth(),
                Carbon::now()->copy()->endOfMonth(),
            ],
        };
    }

    /** @phpstan-return array{y?: mixed, m?: mixed, d?: mixed, iy?: mixed, iw?: mixed} */
    private function mongoVelocityGroupId(string $dateExpr, string $granularity): array
    {
        return match ($granularity) {
            'day' => [
                'y' => ['$year' => $dateExpr],
                'm' => ['$month' => $dateExpr],
                'd' => ['$dayOfMonth' => $dateExpr],
            ],
            'week' => [
                'iy' => ['$isoWeekYear' => $dateExpr],
                'iw' => ['$isoWeek' => $dateExpr],
            ],
            default => [
                'y' => ['$year' => $dateExpr],
                'm' => ['$month' => $dateExpr],
            ],
        };
    }

    /** @phpstan-return array<string, int> */
    private function mongoVelocitySortStage(string $granularity): array
    {
        return match ($granularity) {
            'day' => ['_id.y' => 1, '_id.m' => 1, '_id.d' => 1],
            'week' => ['_id.iy' => 1, '_id.iw' => 1],
            default => ['_id.y' => 1, '_id.m' => 1],
        };
    }

    /** @phpstan-return Collection<string, int> */
    private function keyVelocityCountsFromAggregate(mixed $results, string $granularity): Collection
    {
        return collect($results)->mapWithKeys(function ($item) use ($granularity): array {
            $data = $this->decode($item);
            $groupId = $data['_id'] ?? null;

            return is_array($groupId)
                ? [$this->velocityPeriodKeyFromMongoId($groupId, $granularity) => (int) ($data['count'] ?? 0)]
                : [];
        });
    }

    /**
     * @param  array<string, mixed>  $id
     */
    private function velocityPeriodKeyFromMongoId(array $id, string $granularity): string
    {
        return match ($granularity) {
            'day' => sprintf(
                '%04d-%02d-%02d',
                (int) ($id['y'] ?? $id['year'] ?? 0),
                (int) ($id['m'] ?? $id['month'] ?? 0),
                (int) ($id['d'] ?? $id['day'] ?? 0),
            ),
            'week' => sprintf(
                '%04dW%02d',
                (int) ($id['iy'] ?? 0),
                (int) ($id['iw'] ?? 0),
            ),
            default => sprintf(
                '%04d-%02d',
                (int) ($id['y'] ?? $id['year'] ?? 0),
                (int) ($id['m'] ?? $id['month'] ?? 0),
            ),
        };
    }

    /**
     * @param  Collection<string, int>  $newKeyed
     * @param  Collection<string, int>  $resolvedKeyed
     */
    private function densifyVelocityBuckets(
        Collection $newKeyed,
        Collection $resolvedKeyed,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        string $granularity,
    ): array {
        $timeline = [];

        foreach ($this->enumerateVelocityBuckets($rangeStart, $rangeEnd, $granularity) as ['key' => $key, 'label' => $label]) {
            $new = (int) $newKeyed->get($key, 0);
            $resolved = (int) $resolvedKeyed->get($key, 0);

            $timeline[] = [
                'label' => $label,
                'new' => $new,
                'resolved' => $resolved,
                'delta' => $new - $resolved,
            ];
        }

        return $timeline;
    }

    /**
     * @return iterable<int, array{key: string, label: string}>
     */
    private function enumerateVelocityBuckets(Carbon $rangeStart, Carbon $rangeEnd, string $granularity): iterable
    {
        $locale = app()->getLocale();

        if ($granularity === 'day') {
            $cursor = $rangeStart->copy()->startOfDay();

            while ($cursor->lte($rangeEnd)) {
                yield [
                    'key' => $cursor->format('Y-m-d'),
                    'label' => $cursor->copy()->locale($locale)->translatedFormat('j M'),
                ];
                $cursor->addDay();
            }

            return;
        }

        if ($granularity === 'week') {
            $cursor = $rangeStart->copy()->startOfWeek(Carbon::MONDAY);
            $end = $rangeEnd->copy()->endOfWeek(Carbon::SUNDAY);

            while ($cursor->lte($end)) {
                $isoWeekYear = (int) $cursor->isoWeekYear();
                $isoWeek = (int) $cursor->isoWeek();
                yield [
                    'key' => sprintf('%04dW%02d', $isoWeekYear, $isoWeek),
                    'label' => 'W'.$isoWeek.' '.$isoWeekYear,
                ];
                $cursor->addWeek();
            }

            return;
        }

        $cursor = $rangeStart->copy()->startOfMonth();
        $end = $rangeEnd->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            yield [
                'key' => sprintf('%04d-%02d', $cursor->year, $cursor->month),
                'label' => $cursor->copy()->locale($locale)->translatedFormat('M Y'),
            ];
            $cursor->addMonth();
        }
    }

    public function getSlaAging(string $scanType = 'all'): array
    {
        $now = new UTCDateTime(Carbon::now()->getTimestamp() * 1000);
        $criticalSla = config('sla.critical');
        $highSla = config('sla.high');
        $mediumSla = config('sla.medium');

        $results = collect(FindingStatus::raw(fn ($col) => $col->aggregate([
            ['$match' => $this->findingMatch([
                'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
            ], $scanType)],
            [
                '$addFields' => [
                    'age_days' => ['$divide' => [['$subtract' => [$now, '$created_at']], 86400000]],
                ],
            ],
            [
                '$addFields' => [
                    'age_bucket' => [
                        '$switch' => [
                            'branches' => [
                                ['case' => ['$lte' => ['$age_days', $criticalSla]], 'then' => "0–{$criticalSla}d"],
                                ['case' => ['$lte' => ['$age_days', $highSla]], 'then' => "{$criticalSla}–{$highSla}d"],
                                ['case' => ['$lte' => ['$age_days', $mediumSla]], 'then' => "{$highSla}–{$mediumSla}d"],
                            ],
                            'default' => "{$mediumSla}d+",
                        ],
                    ],
                ],
            ],
            [
                '$group' => [
                    '_id' => ['bucket' => '$age_bucket', 'severity' => '$severity'],
                    'count' => ['$sum' => 1],
                ],
            ],
        ])));

        $buckets = ["0–{$criticalSla}d", "{$criticalSla}–{$highSla}d", "{$highSla}–{$mediumSla}d", "{$mediumSla}d+"];
        $severities = array_map(
            fn (Severity $severity): string => $severity->value,
            Severity::riskCases(),
        );
        $data = collect($buckets)
            ->mapWithKeys(fn ($b): array => [$b => array_fill_keys($severities, 0)])
            ->all();

        foreach ($results as $item) {
            $d = $this->decode($item);
            $bucket = (string) ($d['_id']['bucket'] ?? '');
            $severity = Severity::fromValue($d['_id']['severity'] ?? null);

            if (isset($data[$bucket][$severity->value])) {
                $data[$bucket][$severity->value] += (int) ($d['count'] ?? 0);
            }
        }

        return ['buckets' => $buckets, 'severities' => $severities, 'data' => $data];
    }

    /**
     * @param  'month'|'day'  $granularity  Groepering op as van aanmaakdatum.
     */
    public function getMonthlyOpenFindings(
        int $monthsBack = 6,
        string $granularity = 'month',
        string $scanType = 'all',
    ): array {
        $monthsBack = max(1, min(24, $monthsBack));
        $since = Carbon::now()->subMonths($monthsBack)->startOfMonth();
        $sinceMs = new UTCDateTime($since->getTimestamp() * 1000);
        $isDay = $granularity === 'day';

        $groupId = $isDay
            ? [
                'year' => ['$year' => '$created_at'],
                'month' => ['$month' => '$created_at'],
                'day' => ['$dayOfMonth' => '$created_at'],
            ]
            : [
                'year' => ['$year' => '$created_at'],
                'month' => ['$month' => '$created_at'],
            ];

        $results = FindingStatus::raw(fn ($col) => $col->aggregate([
            [
                '$match' => $this->findingMatch([
                    'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
                    'created_at' => ['$gte' => $sinceMs],
                ], $scanType),
            ],
            ['$group' => ['_id' => $groupId, 'count' => ['$sum' => 1]]],
            ['$sort' => ['_id.year' => 1, '_id.month' => 1, '_id.day' => 1]],
        ]));

        return collect($results)->map(function ($item) use ($isDay): array {
            $data = $this->decode($item);
            $id = $data['_id'] ?? [];

            $label = $isDay
                ? Carbon::createFromDate(
                    (int) ($id['year'] ?? 0),
                    (int) ($id['month'] ?? 1),
                    (int) ($id['day'] ?? 1),
                )->format('d M y')
                : Carbon::createFromDate(
                    (int) ($id['year'] ?? 0),
                    (int) ($id['month'] ?? 1),
                    1,
                )->format('M Y');

            return ['label' => $label, 'count' => (int) ($data['count'] ?? 0)];
        })->values()->all();
    }

    /**
     * Get stacked line graph data for critical open findings per repository over time.
     */
    public function getCriticalFindingsTrendPerRepository(int $monthsBack = 6, string $scanType = 'all'): array
    {
        $monthsBack = max(1, min(24, $monthsBack));
        $since = Carbon::now()->subMonths($monthsBack)->startOfMonth();
        $sinceMs = new UTCDateTime($since->getTimestamp() * 1000);

        $results = collect(FindingStatus::raw(fn ($col) => $col->aggregate([
            [
                '$match' => $this->findingMatch([
                    'severity' => Severity::Critical->value,
                    'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
                    'created_at' => ['$gte' => $sinceMs],
                ], $scanType),
            ],
            [
                '$group' => [
                    '_id' => [
                        'year' => ['$year' => '$created_at'],
                        'month' => ['$month' => '$created_at'],
                        'service_id' => '$service_id',
                    ],
                    'count' => ['$sum' => 1],
                ],
            ],
            [
                '$sort' => [
                    '_id.year' => 1,
                    '_id.month' => 1,
                ],
            ],
        ])));

        // Decode results
        $decoded = [];
        $serviceCounts = [];
        $serviceIds = [];

        foreach ($results as $item) {
            $data = $this->decode($item);
            $id = $data['_id'] ?? [];
            $serviceId = (string) ($id['service_id'] ?? '');
            $year = (int) ($id['year'] ?? 0);
            $month = (int) ($id['month'] ?? 0);
            $count = (int) ($data['count'] ?? 0);
            if ($serviceId === '') {
                continue;
            }
            if ($serviceId === '0') {
                continue;
            }

            $yearMonth = sprintf('%04d-%02d', $year, $month);
            $decoded[] = [
                'yearMonth' => $yearMonth,
                'service_id' => $serviceId,
                'count' => $count,
            ];

            $serviceCounts[$serviceId] = ($serviceCounts[$serviceId] ?? 0) + $count;
            $serviceIds[$serviceId] = true;
        }

        // Get service names
        $services = $this->resolveServices(array_keys($serviceIds));

        // Generate the list of months in chronological order
        $months = [];
        for ($i = $monthsBack; $i >= 0; $i--) {
            $dt = Carbon::now()->subMonths($i);
            $ym = $dt->format('Y-m');
            $months[$ym] = $dt->locale('nl')->translatedFormat('M Y');
        }

        // Identify all unique service IDs
        $allServiceIds = array_keys($serviceCounts);

        // Group findings by service and yearMonth
        $grid = [];
        foreach ($decoded as $row) {
            $ym = $row['yearMonth'];
            if (! isset($months[$ym])) {
                continue;
            }
            $serviceId = $row['service_id'];
            $count = $row['count'];

            $grid[$serviceId][$ym] = $count;
        }

        // Build dataset for each service
        $datasets = [];
        $colors = [
            [
                'border' => 'rgb(34, 211, 238)',      // Cyan
                'bg' => 'rgba(34, 211, 238, 0.12)',
            ],
            [
                'border' => 'rgb(251, 191, 36)',      // Amber
                'bg' => 'rgba(251, 191, 36, 0.12)',
            ],
            [
                'border' => 'rgb(167, 139, 250)',     // Violet
                'bg' => 'rgba(167, 139, 250, 0.12)',
            ],
            [
                'border' => 'rgb(52, 211, 153)',      // Emerald
                'bg' => 'rgba(52, 211, 153, 0.12)',
            ],
            [
                'border' => 'rgb(251, 113, 133)',     // Rose/Red
                'bg' => 'rgba(251, 113, 133, 0.12)',
            ],
            [
                'border' => 'rgb(251, 146, 60)',      // Orange
                'bg' => 'rgba(251, 146, 60, 0.12)',
            ],
            [
                'border' => 'rgb(129, 140, 248)',     // Indigo
                'bg' => 'rgba(129, 140, 248, 0.12)',
            ],
            [
                'border' => 'rgb(244, 114, 182)',     // Pink
                'bg' => 'rgba(244, 114, 182, 0.12)',
            ],
        ];

        $colorIdx = 0;
        foreach ($allServiceIds as $serviceId) {
            $service = $services->get($serviceId);
            $name = $service instanceof Service ? $service->name : 'Onbekend';
            $dataPoints = [];
            foreach (array_keys($months) as $ym) {
                $dataPoints[] = $grid[$serviceId][$ym] ?? 0;
            }

            $color = $colors[$colorIdx % count($colors)];
            $colorIdx++;

            $datasets[] = [
                'label' => $name,
                'data' => $dataPoints,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'fill' => false,
                'tension' => 0.35,
                'pointRadius' => 3,
                'pointBackgroundColor' => $color['border'],
                'pointHoverRadius' => 5,
            ];
        }

        return [
            'labels' => array_values($months),
            'datasets' => $datasets,
        ];
    }

    public function getFindingsByTool(string $scanType = 'all'): array
    {
        $match = $this->findingMatch([
            'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
        ], $scanType);
        $pipeline = [
            ['$match' => $match],
            ['$group' => ['_id' => '$tool.key', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
        ];

        $results = FindingStatus::raw(fn ($col) => $col->aggregate($pipeline));

        return collect($results)
            ->map(fn ($item): array => [
                'tool' => (string) ($this->decode($item)['_id'] ?? 'unknown'),
                'count' => (int) ($this->decode($item)['count'] ?? 0),
            ])
            ->filter(fn (array $item): bool => $item['tool'] !== '' && $item['tool'] !== 'unknown')
            ->values()
            ->all();
    }

    public function mapToolToPurpose(string $toolkey): string
    {
        return match ($toolkey) {
            'trivy' => 'dependencies',
            'gitleaks' => 'secrets',
            'semgrep', 'semgrep oss' => 'code',
            'checkov' => 'azure',
            default => 'unknown',
        };
    }

    public function getMttrByMonth(string|int $monthsBack = 6, string $scanType = 'all'): array
    {
        $match = $this->findingMatch([
            'current_status' => ['$in' => ['resolved', 'closed']],
        ], $scanType);

        if ($monthsBack !== 'all' && (int) $monthsBack > 0) {
            $sinceTimestamp = Carbon::now()->subMonths((int) $monthsBack)->startOfMonth()->getTimestamp();
            $since = new UTCDateTime($sinceTimestamp * 1000);
            $match['created_at'] = ['$gte' => $since];
        }

        $results = FindingStatus::raw(fn ($col) => $col->aggregate([
            [
                '$match' => $match,
            ],
            ['$unwind' => '$history'],
            ['$match' => ['history.to' => ['$in' => ['resolved', 'closed']]]],
            [
                '$addFields' => [
                    'resolved_at' => [
                        '$dateFromString' => [
                            'dateString' => '$history.at',
                            'onError' => null,
                            'onNull' => null,
                        ],
                    ],
                ],
            ],
            ['$match' => ['resolved_at' => ['$ne' => null]]],
            [
                '$addFields' => [
                    'diff_days' => [
                        '$divide' => [['$subtract' => ['$resolved_at', '$created_at']], 86400000],
                    ],
                ],
            ],
            [
                '$group' => [
                    '_id' => [
                        'year' => ['$year' => '$created_at'],
                        'month' => ['$month' => '$created_at'],
                    ],
                    'avg_days' => ['$avg' => '$diff_days'],
                    'count' => ['$sum' => 1],
                ],
            ],
            ['$sort' => ['_id.year' => 1, '_id.month' => 1]],
        ]));

        return collect($results)->map(function ($item): array {
            $data = $this->decode($item);
            $id = $data['_id'] ?? [];

            return [
                'label' => Carbon::createFromDate(
                    (int) ($id['year'] ?? 0),
                    (int) ($id['month'] ?? 1),
                    1
                )->format('M Y'),
                'avg_days' => round((float) ($data['avg_days'] ?? 0), 1),
                'count' => (int) ($data['count'] ?? 0),
            ];
        })->values()->all();
    }

    public function getNewFindingsByPeriod(string $granularity, string $scanType = 'all'): array
    {
        $isDay = $granularity === 'day';
        $since = $isDay
            ? Carbon::now()->subDays(30)->startOfDay()
            : Carbon::now()->subMonths(6)->startOfMonth();

        $sinceMs = new UTCDateTime($since->getTimestamp() * 1000);

        $groupId = $isDay
            ? [
                'year' => ['$year' => '$created_at'],
                'month' => ['$month' => '$created_at'],
                'day' => ['$dayOfMonth' => '$created_at'],
            ]
            : [
                'year' => ['$year' => '$created_at'],
                'month' => ['$month' => '$created_at'],
            ];

        $results = FindingStatus::raw(fn ($col) => $col->aggregate([
            ['$match' => $this->findingMatch(['created_at' => ['$gte' => $sinceMs]], $scanType)],
            ['$group' => ['_id' => $groupId, 'count' => ['$sum' => 1]]],
            ['$sort' => ['_id.year' => 1, '_id.month' => 1, '_id.day' => 1]],
        ]));

        return collect($results)->map(function ($item) use ($isDay): array {
            $data = $this->decode($item);
            $id = $data['_id'] ?? [];

            $label = $isDay
                ? Carbon::createFromDate(
                    (int) ($id['year'] ?? 0),
                    (int) ($id['month'] ?? 1),
                    (int) ($id['day'] ?? 1)
                )->format('d M')
                : Carbon::createFromDate(
                    (int) ($id['year'] ?? 0),
                    (int) ($id['month'] ?? 1),
                    1
                )->format('M Y');

            return ['label' => $label, 'count' => (int) ($data['count'] ?? 0)];
        })->values()->all();
    }

    public function getRepositoryMetrics(string $serviceId, string $scanType = 'all'): array
    {
        $counts = collect(FindingStatus::raw(fn ($col) => $col->aggregate([
            [
                '$match' => $this->findingMatch([
                    'service_id' => $serviceId,
                    'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
                ], $scanType),
            ],
            ['$group' => ['_id' => '$severity', 'count' => ['$sum' => 1]]],
        ])));

        $bySeverity = Severity::zeroCounts();
        $totalOpen = 0;
        foreach ($counts as $item) {
            $data = $this->decode($item);
            $severity = Severity::fromValue($data['_id'] ?? null);
            if (array_key_exists($severity->value, $bySeverity)) {
                $bySeverity[$severity->value] = (int) ($data['count'] ?? 0);
                $totalOpen += $bySeverity[$severity->value];
            }
        }

        $slaBreachesQuery = FindingStatus::where('service_id', $serviceId)
            ->whereIn('current_status', self::ACTIVE_FINDING_STATUSES)
            ->where('severity', Severity::Critical->value)
            ->where('created_at', '<', Carbon::now()->subDays(config('sla.critical')));
        $this->applyScanTypeScope($slaBreachesQuery, $scanType);
        $slaBreaches = $slaBreachesQuery->count();

        $deductions = $this->riskScoreDeduction($bySeverity) + ($slaBreaches * 5);
        $securityScore = max(0, 100 - $deductions);

        // MTTR over last 6 months
        $sinceTimestamp = Carbon::now()->subMonths(6)->startOfMonth()->getTimestamp();
        $since = new UTCDateTime($sinceTimestamp * 1000);

        $mttrRows = collect(FindingStatus::raw(fn ($col) => $col->aggregate([
            [
                '$match' => $this->findingMatch([
                    'service_id' => $serviceId,
                    'current_status' => ['$in' => ['resolved', 'closed']],
                    'created_at' => ['$gte' => $since],
                ], $scanType),
            ],
            ['$unwind' => '$history'],
            ['$match' => ['history.to' => ['$in' => ['resolved', 'closed']]]],
            [
                '$addFields' => [
                    'resolved_at' => [
                        '$dateFromString' => [
                            'dateString' => '$history.at',
                            'onError' => null,
                            'onNull' => null,
                        ],
                    ],
                ],
            ],
            ['$match' => ['resolved_at' => ['$ne' => null]]],
            [
                '$addFields' => [
                    'diff_days' => [
                        '$divide' => [['$subtract' => ['$resolved_at', '$created_at']], 86400000],
                    ],
                ],
            ],
            [
                '$group' => [
                    '_id' => null,
                    'avg_days' => ['$avg' => '$diff_days'],
                    'count' => ['$sum' => 1],
                ],
            ],
        ])));

        $avgMttr = null;
        if ($mttrRows->isNotEmpty()) {
            $first = $this->decode($mttrRows->first());
            if (isset($first['avg_days'])) {
                $avgMttr = round((float) $first['avg_days'], 1);
            }
        }

        // Findings by tool (open)
        $toolRows = collect(FindingStatus::raw(fn ($col) => $col->aggregate([
            [
                '$match' => $this->findingMatch([
                    'service_id' => $serviceId,
                    'current_status' => ['$in' => self::ACTIVE_FINDING_STATUSES],
                ], $scanType),
            ],
            ['$group' => ['_id' => '$tool.key', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
        ])));

        $tools = [];
        foreach ($toolRows as $item) {
            $data = $this->decode($item);
            $toolKey = (string) ($data['_id'] ?? 'unknown');
            if ($toolKey !== '' && $toolKey !== 'unknown') {
                $tools[$toolKey] = (int) ($data['count'] ?? 0);
            }
        }

        return [
            'security_score' => $securityScore,
            'total_open' => $totalOpen,
            'sla_breaches' => $slaBreaches,
            'avg_mttr_days' => $avgMttr,
            'by_severity' => $bySeverity,
            'by_tool' => $tools,
        ];
    }

    private function aggregationCoerceMongoDate(string $fieldName): array
    {
        $ref = '$'.$fieldName;

        return [
            '$switch' => [
                'branches' => [
                    [
                        'case' => ['$eq' => [['$type' => $ref], 'date']],
                        'then' => $ref,
                    ],
                    [
                        'case' => ['$eq' => [['$type' => $ref], 'string']],
                        'then' => [
                            '$ifNull' => [
                                [
                                    '$convert' => [
                                        'input' => $ref,
                                        'to' => 'date',
                                        'onError' => null,
                                        'onNull' => null,
                                    ],
                                ],
                                [
                                    '$dateFromString' => [
                                        'dateString' => $ref,
                                        'format' => '%Y-%m-%d %H:%M:%S',
                                        'onError' => null,
                                        'onNull' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'default' => null,
            ],
        ];
    }

    private function resolveServices(array $ids): Collection
    {
        return Service::whereIn('_id', $ids)
            ->get(['_id', 'name'])
            ->keyBy(fn (Service $service): string => (string) $service->_id);
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function riskScoreDeduction(array $counts): int
    {
        return collect(Severity::riskCases())
            ->sum(fn (Severity $severity): int => ($counts[$severity->value] ?? 0)
                * $severity->riskScoreWeight());
    }

    private function applyScanTypeScope(mixed $query, string $scanType): void
    {
        if ($scanType === 'azure') {
            $query->where('scan_source', 'container');

            return;
        }

        if ($scanType === 'github') {
            $query->where(function ($query): void {
                $query->where('scan_source', 'github')
                    ->orWhereNull('scan_source');
            });
        }
    }

    /**
     * @param  array<string, mixed>  $match
     * @return array<string, mixed>
     */
    private function findingMatch(array $match, string $scanType): array
    {
        if ($scanType === 'azure') {
            return array_merge($match, [
                'scan_source' => 'container',
            ]);
        }

        if ($scanType === 'github') {
            return array_merge($match, [
                '$or' => [
                    ['scan_source' => 'github'],
                    ['scan_source' => null],
                    ['scan_source' => ['$exists' => false]],
                ],
            ]);
        }

        return $match;
    }

    private function decode(mixed $item): array
    {
        $data = json_decode((string) json_encode($item), true) ?? [];

        // Laravel MongoDB Eloquent maps _id → id on model instances returned from raw()
        // aggregations. Restore the original key so all callers can use $data['_id'].
        if (! array_key_exists('_id', $data) && array_key_exists('id', $data)) {
            $data['_id'] = $data['id'];
        }

        return $data;
    }
}
