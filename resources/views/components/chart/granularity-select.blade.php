@props([
    'label' => '',
    /** @var list<array{value: scalar, label: string}> $options */
    'options' => [],
])

@php
    $wireModel = $attributes->wire('model')?->value();
@endphp

@if (($labelTrimmed = trim((string) $label)) !== '')
    <label class="flex flex-col gap-1">
        <span class="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">
            {{ $labelTrimmed }}
            @if ($wireModel)
                <x-icon.spinner-arc
                    wire:loading
                    wire:target="{{ $wireModel }}"
                    class="size-3 animate-spin text-slate-400"
                />
            @endif
        </span>
        <select
            {{ $attributes->merge([
                'class' =>
                    'w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30',
            ]) }}
        >
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
    </label>
@else
    <div class="relative flex w-full items-center">
        <select
            {{ $attributes->merge([
                'class' =>
                    'w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 pr-8 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30',
            ]) }}
        >
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
        @if ($wireModel)
            <div
                wire:loading
                wire:target="{{ $wireModel }}"
                class="pointer-events-none absolute right-2.5 flex items-center"
            >
                <x-icon.spinner-arc class="size-3.5 animate-spin text-slate-400" />
            </div>
        @endif
    </div>
@endif
