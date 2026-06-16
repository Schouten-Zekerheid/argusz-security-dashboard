@props([
    'closeAction' => null,
    'maxWidth' => 'md',
])

<div class="fixed inset-0 z-50 flex items-center justify-center">
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        @if ($closeAction) wire:click="{{ $closeAction }}" @endif
    ></div>
    <div
        {{ $attributes->merge(['class' => 'relative z-10 w-full rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-2xl ' . ($maxWidth === 'sm' ? 'max-w-sm' : 'max-w-md')]) }}>
        {{ $slot }}
    </div>
</div>
