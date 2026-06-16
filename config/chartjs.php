<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Chart.js plugin options (trend analysis UI)
    |--------------------------------------------------------------------------
    */

    'layout' => [
        'responsive' => true,
        'maintainAspectRatio' => false,
    ],

    'legend' => [
        'hidden' => [
            'display' => false,
        ],
        'top' => [
            'position' => 'top',
            'labels' => [
                'color' => '#94a3b8',
                'font' => ['size' => 11],
                'boxWidth' => 12,
            ],
        ],
        'bottom' => [
            'position' => 'bottom',
            'labels' => [
                'color' => '#94a3b8',
                'font' => ['size' => 11],
                'padding' => 12,
                'boxWidth' => 12,
            ],
        ],
    ],

    'tooltip' => [
        'backgroundColor' => '#0f172a',
        'borderColor' => '#334155',
        'borderWidth' => 1,
        'titleColor' => '#94a3b8',
        'bodyColor' => '#e2e8f0',
    ],

    'scales' => [
        'tick_color' => '#64748b',
        'grid_color' => '#1e293b',
    ],

    'palette' => [
        'doughnut_segments' => [
            'rgba(34, 211, 238, 0.85)',
            'rgba(251, 191, 36, 0.85)',
            'rgba(167, 139, 250, 0.85)',
            'rgba(74, 222, 128, 0.85)',
            'rgba(248, 113, 113, 0.85)',
        ],
        'sla' => [
            'critical' => 'rgba(239, 68, 68, 0.75)',
            'high' => 'rgba(249, 115, 22, 0.75)',
            'medium' => 'rgba(251, 191, 36, 0.75)',
            'low' => 'rgba(163, 230, 53, 0.75)',
        ],
    ],

    'dataset' => [
        'line_open_findings_monthly' => [
            'borderColor' => 'rgb(34, 211, 238)',
            'backgroundColor' => 'rgba(34, 211, 238, 0.08)',
            'tension' => 0.35,
            'pointBackgroundColor' => 'rgb(34, 211, 238)',
            'pointRadius' => 4,
        ],
        'doughnut' => [
            'borderColor' => '#0f172a',
            'borderWidth' => 2,
        ],
        'velocity_bar_new' => [
            'backgroundColor' => 'rgba(239, 68, 68, 0.55)',
            'borderColor' => 'rgba(239, 68, 68, 0.85)',
            'borderWidth' => 1,
            'borderRadius' => 4,
            'stack' => 'stack',
        ],
        'velocity_bar_resolved' => [
            'backgroundColor' => 'rgba(74, 222, 128, 0.55)',
            'borderColor' => 'rgba(74, 222, 128, 0.85)',
            'borderWidth' => 1,
            'borderRadius' => 4,
            'stack' => 'stack',
        ],
        'velocity_line_new' => [
            'borderColor' => 'rgb(248, 113, 113)',
            'backgroundColor' => 'rgba(248, 113, 113, 0.12)',
            'borderWidth' => 2,
            'tension' => 0.35,
            'pointRadius' => 4,
            'pointBackgroundColor' => 'rgb(248, 113, 113)',
        ],
        'velocity_line_resolved' => [
            'borderColor' => 'rgb(74, 222, 128)',
            'backgroundColor' => 'rgba(74, 222, 128, 0.12)',
            'borderWidth' => 2,
            'tension' => 0.35,
            'pointRadius' => 4,
            'pointBackgroundColor' => 'rgb(74, 222, 128)',
        ],
        'velocity_line_delta' => [
            'borderColor' => 'rgb(251, 191, 36)',
            'backgroundColor' => 'rgba(251, 191, 36, 0.08)',
            'borderWidth' => 2,
            'tension' => 0.35,
            'pointRadius' => 4,
            'pointBackgroundColor' => 'rgb(251, 191, 36)',
            'borderDash' => [6, 3],
        ],
        'bar_mttr' => [
            'backgroundColor' => 'rgba(167, 139, 250, 0.6)',
            'borderColor' => 'rgba(167, 139, 250, 0.9)',
            'borderWidth' => 1,
            'borderRadius' => 4,
        ],
        'line_mttr' => [
            'borderColor' => 'rgba(167, 139, 250, 0.95)',
            'backgroundColor' => 'rgba(167, 139, 250, 0.1)',
            'borderWidth' => 2,
            'tension' => 0.35,
            'pointRadius' => 4,
            'pointBackgroundColor' => 'rgba(167, 139, 250, 0.95)',
        ],
        'bar_new_findings' => [
            'backgroundColor' => 'rgba(74, 222, 128, 0.55)',
            'borderColor' => 'rgba(74, 222, 128, 0.85)',
            'borderWidth' => 1,
            'borderRadius' => 4,
        ],
        'line_new_findings' => [
            'borderColor' => 'rgb(74, 222, 128)',
            'backgroundColor' => 'rgba(74, 222, 128, 0.1)',
            'borderWidth' => 2,
            'tension' => 0.35,
            'pointRadius' => 4,
            'pointBackgroundColor' => 'rgb(74, 222, 128)',
        ],
        'bar_critical_repo' => [
            'backgroundColor' => 'rgba(239, 68, 68, 0.6)',
            'borderColor' => 'rgba(239, 68, 68, 0.9)',
            'borderWidth' => 1,
            'borderRadius' => 4,
        ],
        'sla_segment' => [
            'borderRadius' => 3,
            'stack' => 'sla',
        ],
    ],

];
