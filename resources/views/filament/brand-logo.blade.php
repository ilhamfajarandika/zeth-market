@php
    $appName = config('app.name', 'Zeth Market');
@endphp

<div style="display: flex; align-items: center; gap: 0.625rem;">
    <img
        src="{{ asset('favicon-32x32.png') }}"
        alt="{{ $appName }}"
        style="height: 2rem; width: 2rem; flex-shrink: 0; border-radius: 0.375rem; object-fit: contain;"
    />

    <span style="font-size: 1rem; font-weight: 700; letter-spacing: -0.02em; text-transform: uppercase;">
        {{ $appName }}
    </span>
</div>
