@props(['class' => 'h-9 w-auto'])

@if (config('security.branding.logo'))
    <img
        src="{{ asset(config('security.branding.logo')) }}"
        alt="{{ config('security.branding.logo_alt') }}"
        class="{{ $class }}"
    >
@endif
