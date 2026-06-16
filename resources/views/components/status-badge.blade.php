{{--
    $normalized, $classes, $text: public properties on App\View\Components\StatusBadge,
    set in the constructor from the Blade attribute `status` (e.g. <x-status-badge status="open" />).
--}}
<span class="{{ $classes }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">
    <span class="mr-1.5 inline-flex size-4 items-center justify-center rounded-full bg-slate-950/60">
        @if ($normalized === 'snoozed')
            <x-icon.snooze class="size-2.5" />
        @elseif ($normalized === 'returning')
            <span class="text-[10px] font-bold">↺</span>
        @elseif ($normalized === 'critical' || $normalized === 'open')
            <span class="text-[10px] font-bold">!</span>
        @elseif ($normalized === 'warning')
            <span class="text-[10px] font-bold">~</span>
        @elseif ($normalized === 'resolved' || $normalized === 'closed' || $normalized === 'healthy')
            <span class="text-[10px] font-bold">✓</span>
        @else
            <span class="text-[10px] font-bold">?</span>
        @endif
    </span>
    {{ $text }}
</span>
