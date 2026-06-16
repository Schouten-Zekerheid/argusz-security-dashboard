<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Argusz — Trend Analysis Report</title>
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            /* Pure white background */
            color: #0f172a;
            /* Slate 900 for high text contrast */
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            -webkit-print-color-adjust: exact;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        .page-container {
            width: 297mm;
            height: 210mm;
            box-sizing: border-box;
            padding: 12mm 15mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: #ffffff;
            overflow: hidden;
            page-break-after: always;
        }

        .page-container:last-of-type {
            page-break-after: avoid;
        }
    </style>
</head>

<body>

    <!-- PAGE 1: Dashboard Overview -->
    <div class="page-container">
        <div>
            <!-- Header Banner -->
            <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-3">
                    @include('components.logo-app', ['class' => 'h-9 w-auto text-slate-800'])
                    <div class="h-6 w-px bg-slate-200"></div>
                    <div>
                        <h1 class="text-lg font-bold leading-none tracking-tight text-slate-900">ARGUSZ</h1>
                        <p class="mt-0.5 text-[9px] font-medium text-slate-500">Trend Analysis Security Report</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-slate-400">Generated on</p>
                    <p class="text-xs font-semibold text-slate-700">{{ now()->format('d M Y \a\t H:i') }}</p>
                </div>
            </div>

            <!-- Meta Filter Info -->
            <div class="mb-6 grid grid-cols-3 gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div>
                    <span class="block text-[9px] font-semibold uppercase tracking-wider text-slate-500">Scan
                        Type</span>
                    <span class="text-xs font-medium text-slate-800">{{ $scanTypeLabel }}</span>
                </div>
                <div>
                    <span class="block text-[9px] font-semibold uppercase tracking-wider text-slate-500">MTTR
                        Window</span>
                    <span class="text-xs font-medium text-slate-800">
                        {{ match ($mttrMonthsBack) {
                            '3' => 'Last quarter',
                            '6' => 'Half year',
                            '12' => 'Year',
                            default => 'All-time',
                        } }}
                    </span>
                </div>
                <div>
                    <span class="block text-[9px] font-semibold uppercase tracking-wider text-slate-500">Trend
                        Grouping</span>
                    <span class="text-xs font-medium text-slate-800">
                        {{ match ($trendGranularity) {
                            'day' => 'Daily',
                            'week' => 'Weekly',
                            default => 'Monthly',
                        } }}
                    </span>
                </div>
            </div>

            <!-- Main Score Card -->
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex items-center gap-8">
                    <!-- Circular Gauge -->
                    @php
                        $circumference = 2 * M_PI * 36;
                        $strokeDashoffset = $circumference - ($overallSecurityScore / 100) * $circumference;
                    @endphp
                    <div class="relative flex-shrink-0">
                        <svg
                            class="size-24 -rotate-90"
                            viewBox="0 0 80 80"
                        >
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                fill="none"
                                stroke="#e2e8f0"
                                stroke-width="7"
                            />
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                fill="none"
                                stroke-width="7"
                                stroke-linecap="round"
                                style="stroke-dasharray: {{ $circumference }}; stroke-dashoffset: {{ $strokeDashoffset }};"
                                class="@if ($overallSecurityScore >= 80) text-green-600 @elseif($overallSecurityScore >= 50) text-yellow-600 @else text-red-600 @endif"
                                stroke="currentColor"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span
                                class="text-xl font-bold tabular-nums text-slate-900">{{ $overallSecurityScore }}</span>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex-1">
                        <h2 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Overall Security
                            Score</h2>
                        <p class="mt-0.5 text-xs font-normal text-slate-700">
                            The score reflects the current security status based on open findings and SLA breaches.
                        </p>
                        <p class="mt-1 text-[9px] text-slate-400">
                            Formula: 100 − (critical × 10 + high × 5 + medium × 2 + low × 1) − (SLA breaches × 5)
                        </p>
                    </div>

                    <!-- Legend -->
                    <div class="flex shrink-0 flex-col gap-1 border-l border-slate-200 pl-6">
                        <div class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-green-500"></span>
                            <span class="text-[10px] font-medium text-slate-500">Good (≥ 80)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-yellow-500"></span>
                            <span class="text-[10px] font-medium text-slate-500">Moderate (50 – 79)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-red-500"></span>
                            <span class="text-[10px] font-medium text-slate-500">Critical (&lt; 50)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards Grid -->
            <div class="mb-6 grid grid-cols-4 gap-4">
                <!-- KPI 1 -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-500">Open Findings</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">{{ $kpiSummary['total_open'] }}</p>
                    <p class="mt-1 text-[9px] text-slate-400">total active</p>
                </div>

                <!-- KPI 2 -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-500">SLA Breaches</p>
                    <p
                        class="@if ($kpiSummary['sla_breaches'] > 0) text-red-600 @else text-green-600 @endif mt-0.5 text-2xl font-bold tabular-nums">
                        {{ $kpiSummary['sla_breaches'] }}
                    </p>
                    <p class="mt-1 text-[9px] text-slate-400">critical (&gt; 7 days)</p>
                </div>

                <!-- KPI 3 -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-500">Average MTTR</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-violet-600">
                        {{ $kpiSummary['avg_mttr_days'] > 0 ? $kpiSummary['avg_mttr_days'] : '—' }}
                    </p>
                    <p class="mt-1 text-[9px] text-slate-400">days to resolution</p>
                </div>

                <!-- KPI 4 -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-500">Trend open findings</p>
                    <div class="mt-0.5 flex items-center gap-1">
                        @if ($kpiSummary['trend_direction'] === 'up')
                            <p class="text-2xl font-bold tabular-nums text-red-600">+{{ $kpiSummary['trend_pct'] }}%
                            </p>
                            <svg
                                class="size-4 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M7 17L17 7M17 7H7M17 7v10"
                                />
                            </svg>
                        @elseif ($kpiSummary['trend_direction'] === 'down')
                            <p class="text-2xl font-bold tabular-nums text-green-600">-{{ $kpiSummary['trend_pct'] }}%
                            </p>
                            <svg
                                class="size-4 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M7 7l10 10M17 17H7M17 17V7"
                                />
                            </svg>
                        @else
                            <p class="text-2xl font-bold tabular-nums text-slate-400">—</p>
                        @endif
                    </div>
                    <p class="mt-1 text-[9px] text-slate-400">{{ $trendComparisonLabel }}</p>
                </div>
            </div>

            <!-- Freshness Info -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Scan Freshness and
                    Ingestion</h3>
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div class="flex items-center gap-2 text-slate-600">
                        <span
                            class="{{ $scanFreshness['github']['is_stale'] ? 'bg-amber-500' : 'bg-slate-400' }} size-1.5 rounded-full"
                        ></span>
                        Github scans: <span
                            class="font-semibold text-slate-800">{{ $scanFreshness['github']['label'] }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600">
                        <span
                            class="{{ $scanFreshness['azure']['is_stale'] ? 'bg-amber-500' : 'bg-slate-400' }} size-1.5 rounded-full"
                        ></span>
                        Azure/Containers scans: <span
                            class="font-semibold text-slate-800">{{ $scanFreshness['azure']['label'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Footer -->
        <div class="flex justify-between border-t border-slate-200 pt-3 text-[9px] text-slate-400">
            <span>Argusz Trend Analysis Security Report</span>
            <span>Page 1 of 4</span>
        </div>
    </div>

    <!-- PAGE 2: Open Findings & Critical Trend -->
    <div class="page-container">
        <div>
            <!-- Header Banner -->
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex items-center gap-2">
                    @include('components.logo-app', ['class' => 'h-6 w-auto text-slate-700'])
                    <div class="h-4 w-px bg-slate-200"></div>
                    <h2 class="text-sm font-bold text-slate-800">Open Findings & Trends</h2>
                </div>
                <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">Argusz Security</span>
            </div>

            <!-- Open findings & Purpose Doughnut side-by-side -->
            <div class="mb-4 grid grid-cols-2 gap-4">
                <!-- Open findings Line -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Open
                        findings over time</h3>
                    <p class="mb-2 text-[9px] text-slate-400">Historical progression of active findings.</p>
                    <div class="relative h-44">
                        <canvas id="chartOpenFindings"></canvas>
                    </div>
                </div>

                <!-- Doughnut kind -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Findings
                        by type</h3>
                    <p class="mb-2 text-[9px] text-slate-400">Active distribution categorized by scope.</p>
                    <div class="relative h-44">
                        <canvas id="chartFindingsByPurpose"></canvas>
                    </div>
                </div>
            </div>

            <!-- Critical findings trend per repo -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Critical open
                    findings per repository</h3>
                <p class="mb-2 text-[9px] text-slate-400">Trendline of critical security issues per active service.</p>
                <div class="relative h-48">
                    <canvas id="chartCriticalFindings"></canvas>
                </div>
            </div>
        </div>

        <!-- Inline Footer -->
        <div class="flex justify-between border-t border-slate-200 pt-3 text-[9px] text-slate-400">
            <span>Argusz Trend Analysis Security Report</span>
            <span>Page 2 of 4</span>
        </div>
    </div>

    <!-- PAGE 3: MTTR, New Findings, Velocity & SLA -->
    <div class="page-container">
        <div>
            <!-- Header Banner -->
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex items-center gap-2">
                    @include('components.logo-app', ['class' => 'h-6 w-auto text-slate-700'])
                    <div class="h-4 w-px bg-slate-200"></div>
                    <h2 class="text-sm font-bold text-slate-800">Resolution Time, Velocity & SLA Aging</h2>
                </div>
                <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">Argusz Security</span>
            </div>

            <!-- MTTR & New findings side by side -->
            <div class="mb-4 grid grid-cols-2 gap-4">
                <!-- MTTR Chart -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Average
                        resolution time (MTTR)</h3>
                    <p class="mb-2 text-[9px] text-slate-400">Average days to resolution per month.</p>
                    <div class="relative h-44">
                        <canvas id="chartMttr"></canvas>
                    </div>
                </div>

                <!-- New findings Chart -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">New
                        findings per period</h3>
                    <p class="mb-2 text-[9px] text-slate-400">Number of newly created findings.</p>
                    <div class="relative h-44">
                        <canvas id="chartNewFindings"></canvas>
                    </div>
                </div>
            </div>

            <!-- Velocity & SLA side by side -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Velocity Chart -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Velocity
                        (New vs. Resolved)</h3>
                    <p class="mb-2 text-[9px] text-slate-400">Rate of detection vs. resolution.</p>
                    <div class="relative h-44">
                        <canvas id="chartVelocity"></canvas>
                    </div>
                </div>

                <!-- SLA Aging Chart -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">SLA
                        Aging</h3>
                    <p class="mb-2 text-[9px] text-slate-400">Number of open findings by age category.</p>
                    <div class="relative h-44">
                        <canvas id="chartSlaAging"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Footer -->
        <div class="flex justify-between border-t border-slate-200 pt-3 text-[9px] text-slate-400">
            <span>Argusz Trend Analysis Security Report</span>
            <span>Page 3 of 4</span>
        </div>
    </div>

    <!-- PAGE 4: Repository Risk Scores & Side-by-Side Comparison -->
    <div class="page-container">
        <div>
            <!-- Header Banner -->
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex items-center gap-2">
                    @include('components.logo-app', ['class' => 'h-6 w-auto text-slate-700'])
                    <div class="h-4 w-px bg-slate-200"></div>
                    <h2 class="text-sm font-bold text-slate-800">Risk Score & Comparison</h2>
                </div>
                <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">Argusz Security</span>
            </div>

            @if ($metricsA && $metricsB)
                <div class="grid grid-cols-2 items-start gap-6">
                    <!-- Column 1: Risk Scores List -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Risk score per repository (Top 6)</h3>
                        <p class="mb-4 text-[9px] text-slate-400">Weighted risk per service based on severity.</p>
                        <div class="grid grid-cols-1 gap-3.5">
                            @foreach (array_slice($riskScoreByRepository, 0, 6) as $item)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <div class="mb-1.5 flex items-center justify-between text-xs">
                                        <span
                                            class="truncate font-semibold text-slate-700">{{ $item['repository'] }}</span>
                                        <span
                                            class="@if ($item['pct'] >= 75) text-red-655 @elseif($item['pct'] >= 40) text-orange-600 @else text-green-600 @endif font-bold"
                                        >
                                            {{ $item['score'] }}
                                        </span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                        <div
                                            class="@if ($item['pct'] >= 75) bg-red-500 @elseif($item['pct'] >= 40) bg-orange-500 @else bg-green-500 @endif h-full rounded-full"
                                            style="width: {{ $item['pct'] }}%"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Column 2: Side-by-Side Comparison -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Repository
                            Comparison</h3>
                        <p class="mb-4 text-[9px] text-slate-400">Compare statistics and scores side-by-side.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Repo A Card -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                                <h4 class="mb-2 truncate text-xs font-bold text-slate-900">
                                    {{ $metricsA['repository_name'] }}</h4>
                                <div class="space-y-1.5 text-[10px]">
                                    <div
                                        class="flex justify-between border-b border-slate-200 pb-1 font-medium text-slate-500">
                                        Score: <span
                                            class="font-bold text-slate-800">{{ $metricsA['security_score'] }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between border-b border-slate-200 pb-1 font-medium text-slate-500">
                                        Open: <span
                                            class="font-bold text-slate-800">{{ $metricsA['total_open'] }}</span>
                                    </div>
                                    <div class="flex justify-between font-medium text-slate-500">SLA: <span
                                            class="font-bold text-red-600"
                                        >{{ $metricsA['sla_breaches'] }}</span></div>
                                </div>
                            </div>
                            <!-- Repo B Card -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                                <h4 class="mb-2 truncate text-xs font-bold text-slate-900">
                                    {{ $metricsB['repository_name'] }}</h4>
                                <div class="space-y-1.5 text-[10px]">
                                    <div
                                        class="flex justify-between border-b border-slate-200 pb-1 font-medium text-slate-500">
                                        Score: <span
                                            class="font-bold text-slate-800">{{ $metricsB['security_score'] }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between border-b border-slate-200 pb-1 font-medium text-slate-500">
                                        Open: <span
                                            class="font-bold text-slate-800">{{ $metricsB['total_open'] }}</span>
                                    </div>
                                    <div class="flex justify-between font-medium text-slate-500">SLA: <span
                                            class="font-bold text-red-600"
                                        >{{ $metricsB['sla_breaches'] }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- No Comparison Selected: Show Risk Scores in large 3-column layout -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Risk score
                        per repository (Top 6)</h3>
                    <p class="mb-5 text-[9px] text-slate-400">Weighted risk per service based on severity.</p>
                    <div class="grid grid-cols-3 gap-6">
                        @foreach (array_slice($riskScoreByRepository, 0, 6) as $item)
                            <div
                                class="flex h-24 flex-col justify-between rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center justify-between text-xs">
                                    <span
                                        class="truncate text-sm font-semibold text-slate-800">{{ $item['repository'] }}</span>
                                    <span
                                        class="@if ($item['pct'] >= 75) text-red-600 @elseif($item['pct'] >= 40) text-orange-600 @else text-green-600 @endif text-base font-bold"
                                    >
                                        {{ $item['score'] }}
                                    </span>
                                </div>
                                <div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                        <div
                                            class="@if ($item['pct'] >= 75) bg-red-500 @elseif($item['pct'] >= 40) bg-orange-500 @else bg-green-500 @endif h-full rounded-full"
                                            style="width: {{ $item['pct'] }}%"
                                        ></div>
                                    </div>
                                    <div class="mt-2 flex gap-3 font-mono text-[10px] text-slate-400">
                                        @if ($item['critical'] > 0)
                                            <span class="text-red-500">C: {{ $item['critical'] }}</span>
                                        @endif
                                        @if ($item['high'] > 0)
                                            <span class="text-orange-500">H: {{ $item['high'] }}</span>
                                        @endif
                                        @if ($item['medium'] > 0)
                                            <span class="text-amber-600">M: {{ $item['medium'] }}</span>
                                        @endif
                                        @if ($item['low'] > 0)
                                            <span class="text-green-600">L: {{ $item['low'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Inline Footer -->
        <div class="flex justify-between border-t border-slate-200 pt-3 text-[9px] text-slate-400">
            <span>Argusz Trend Analysis Security Report</span>
            <span>Page 4 of 4</span>
        </div>
    </div>

    <!-- Chart Scripts -->
    <script>
        const chartOptions = {
            animation: false,
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    right: 25,
                    left: 8,
                    top: 5,
                    bottom: 5
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 9
                        }
                    }
                },
                y: {
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 9
                        }
                    },
                    beginAtZero: true
                }
            }
        };

        // 1. Open Findings (Line)
        new Chart(document.getElementById('chartOpenFindings').getContext('2d'), {
            type: 'line',
            data: {
                labels: @js(collect($monthlyOpenFindings)->pluck('label')),
                datasets: [{
                    label: 'Open findings',
                    data: @js(collect($monthlyOpenFindings)->pluck('count')),
                    borderColor: '#06b6d4', // Cyan 500
                    backgroundColor: 'rgba(6, 182, 212, 0.05)',
                    borderWidth: 2,
                    tension: 0.35,
                    pointRadius: 2,
                    fill: true
                }]
            },
            options: chartOptions
        });

        // 2. Findings By Kind (Doughnut)
        new Chart(document.getElementById('chartFindingsByPurpose').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @js(collect($findingsByPurpose)->pluck('purpose')),
                datasets: [{
                    data: @js(collect($findingsByPurpose)->pluck('count')),
                    backgroundColor: [
                        'rgba(6, 182, 212, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        right: 25,
                        left: 10,
                        top: 5,
                        bottom: 5
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            color: '#475569',
                            font: {
                                size: 9
                            },
                            boxWidth: 8
                        }
                    }
                }
            }
        });

        // 3. Critical findings per repo (Line)
        const criticalTrendData = @js($criticalFindingsTrend);
        new Chart(document.getElementById('chartCriticalFindings').getContext('2d'), {
            type: 'line',
            data: {
                labels: criticalTrendData.labels || [],
                datasets: (criticalTrendData.datasets || []).map((ds, idx) => {
                    ds.fill = false;
                    ds.borderWidth = 2;
                    ds.pointRadius = 2.5;
                    return ds;
                })
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        right: 40,
                        left: 10,
                        top: 5,
                        bottom: 5
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#475569',
                            font: {
                                size: 8
                            },
                            boxWidth: 10,
                            padding: 8
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 8
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 8
                            }
                        },
                        beginAtZero: true
                    }
                }
            }
        });

        // 4. Average MTTR (Line)
        new Chart(document.getElementById('chartMttr').getContext('2d'), {
            type: 'line',
            data: {
                labels: @js(collect($mttrByMonth)->pluck('label')),
                datasets: [{
                    label: 'Avg. days',
                    data: @js(collect($mttrByMonth)->pluck('avg_days')),
                    borderColor: '#8b5cf6', // Purple 500
                    backgroundColor: 'rgba(139, 92, 246, 0.05)',
                    borderWidth: 2,
                    tension: 0.35,
                    pointRadius: 2,
                    fill: true
                }]
            },
            options: chartOptions
        });

        // 5. New findings (Line)
        new Chart(document.getElementById('chartNewFindings').getContext('2d'), {
            type: 'line',
            data: {
                labels: @js(collect($newFindingsByPeriod)->pluck('label')),
                datasets: [{
                    label: 'New findings',
                    data: @js(collect($newFindingsByPeriod)->pluck('count')),
                    borderColor: '#10b981', // Green 500
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderWidth: 2,
                    tension: 0.35,
                    pointRadius: 2,
                    fill: true
                }]
            },
            options: chartOptions
        });

        // 6. Velocity (Line)
        new Chart(document.getElementById('chartVelocity').getContext('2d'), {
            type: 'line',
            data: {
                labels: @js(collect($findingsVelocity)->pluck('label')),
                datasets: [{
                        label: 'New',
                        data: @js(collect($findingsVelocity)->pluck('new')),
                        borderColor: '#ef4444', // Red 500
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2,
                        fill: false
                    },
                    {
                        label: 'Resolved',
                        data: @js(collect($findingsVelocity)->pluck('resolved')),
                        borderColor: '#10b981', // Green 500
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2,
                        fill: false
                    }
                ]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        right: 25,
                        left: 8,
                        top: 5,
                        bottom: 5
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#475569',
                            font: {
                                size: 9
                            },
                            boxWidth: 8
                        }
                    }
                },
                scales: chartOptions.scales
            }
        });

        // 7. SLA Aging (Stacked Bar)
        new Chart(document.getElementById('chartSlaAging').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @js($slaAging['buckets']),
                datasets: [{
                        label: 'Critical',
                        data: @js(collect($slaAging['buckets'])->map(fn($b) => $slaAging['data'][$b]['CRITICAL'] ?? 0)),
                        backgroundColor: 'rgba(239, 68, 68, 0.75)',
                        stack: 'sla'
                    },
                    {
                        label: 'High',
                        data: @js(collect($slaAging['buckets'])->map(fn($b) => $slaAging['data'][$b]['HIGH'] ?? 0)),
                        backgroundColor: 'rgba(249, 115, 22, 0.75)',
                        stack: 'sla'
                    },
                    {
                        label: 'Medium',
                        data: @js(collect($slaAging['buckets'])->map(fn($b) => $slaAging['data'][$b]['MEDIUM'] ?? 0)),
                        backgroundColor: 'rgba(251, 191, 36, 0.75)',
                        stack: 'sla'
                    },
                    {
                        label: 'Low',
                        data: @js(collect($slaAging['buckets'])->map(fn($b) => $slaAging['data'][$b]['LOW'] ?? 0)),
                        backgroundColor: 'rgba(163, 230, 53, 0.75)',
                        stack: 'sla'
                    }
                ]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        right: 25,
                        left: 8,
                        top: 5,
                        bottom: 5
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#475569',
                            font: {
                                size: 8
                            },
                            boxWidth: 8
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 8
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 8
                            }
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>
