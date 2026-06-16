<a
    href="{{ $url }}"
    @if ($external) target="_blank" @endif
    @class([
        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
        'bg-slate-800 text-slate-100' => $isActive,
        'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100' => !$isActive,
    ])
    :class="sidebarCollapsed ? 'justify-center px-2' : ''"
    :title="sidebarCollapsed ? '{{ $label }}' : ''"
>
    {{ $icon }}
    <span
        x-show="!sidebarCollapsed"
        x-cloak
    >
        {{ $label }}
    </span>
</a>
