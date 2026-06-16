<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Blade: resources/views/components/status-badge.blade.php
 *
 * The `status` HTML attribute maps to the constructor's $status argument, for example:
 * `<x-status-badge :status="$value" />`.
 */
class StatusBadge extends Component
{
    public string $normalized;

    public string $classes;

    public string $text;

    public function __construct(string $status = 'unknown')
    {
        $this->normalized = strtolower($status);
        $this->classes = match ($this->normalized) {
            'critical' => 'bg-red-500/20 text-red-200 ring-1 ring-red-400/70',
            'warning' => 'bg-amber-500/20 text-amber-200 ring-1 ring-amber-400/40',
            'open' => 'bg-red-500/20 text-red-200 ring-1 ring-red-400/60',
            'returning' => 'bg-orange-500/20 text-orange-200 ring-1 ring-orange-400/60',
            'snoozed' => 'bg-violet-500/20 text-violet-200 ring-1 ring-violet-400/60',
            'resolved', 'closed' => 'bg-green-500/20 text-green-200 ring-1 ring-green-400/60',
            'unknown' => 'bg-slate-500/20 text-slate-300 ring-1 ring-slate-400/40',
            default => 'bg-green-500/20 text-green-200 ring-1 ring-green-400/40',
        };
        $this->text = match ($this->normalized) {
            'critical' => 'CRITICAL',
            'warning' => 'WARNING',
            'open' => 'OPEN',
            'returning' => 'RETURNING',
            'snoozed' => 'SNOOZED',
            'resolved', 'closed' => strtoupper($this->normalized),
            'unknown' => 'UNKNOWN',
            default => 'HEALTHY',
        };
    }

    public function render(): View
    {
        return view('components.status-badge');
    }
}
