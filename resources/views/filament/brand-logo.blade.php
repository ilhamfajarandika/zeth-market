@php
    $appName = config('app.name', 'Zeth Market');
    $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists('logo/logo.webp')
        ? \Illuminate\Support\Facades\Storage::disk('public')->url('logo/logo.webp')
        : null;
@endphp

<div style="display: flex; align-items: center; gap: 0.625rem;">
    @if ($logoUrl)
        <img
            src="{{ $logoUrl }}"
            alt="{{ $appName }}"
            style="height: 2rem; width: 2rem; flex-shrink: 0; border-radius: 0.375rem; object-fit: contain;"
        />
    @else
        <span style="display: grid; height: 2rem; width: 2rem; flex-shrink: 0; place-items: center; border-radius: 0.375rem; background-color: #e50914; font-size: 0.875rem; font-weight: 700; color: #ffffff;">
            {{ strtoupper(substr($appName, 0, 1)) }}
        </span>
    @endif

    <span style="font-size: 1rem; font-weight: 700; letter-spacing: -0.02em; text-transform: uppercase;">
        {{ $appName }}
    </span>
</div>
