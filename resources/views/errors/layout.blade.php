@php
    $appName = config('app.name', 'Zeth Market');
    $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists('logo/logo.webp')
        ? \Illuminate\Support\Facades\Storage::disk('public')->url('logo/logo.webp')
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — @yield('title') | {{ $appName }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background-color: #0b0b0d;
            color: #f5f5f5;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            border-top: 2px solid #e50914;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background-color: #0b0b0d;
        }
        .topbar-inner {
            max-width: 80rem;
            margin: 0 auto;
            height: 4rem;
            padding: 0 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #f5f5f5;
        }
        .brand-logo {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.375rem;
            object-fit: contain;
        }
        .brand-initial {
            width: 2.25rem;
            height: 2.25rem;
            display: grid;
            place-items: center;
            background-color: #e50914;
            font-weight: 700;
            font-size: 1rem;
            clip-path: polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px));
        }
        .brand-name {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            line-height: 1;
        }
        .brand-sub {
            margin-top: 0.25rem;
            font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace;
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #a1a1aa;
            line-height: 1;
        }
        .main {
            flex: 1;
            position: relative;
            display: grid;
            place-items: center;
            padding: 3rem 1.25rem;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 64px 64px;
        }
        .card {
            width: 100%;
            max-width: 34rem;
            background-color: #141416;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-left: 3px solid #e50914;
            border-radius: 0.5rem;
            padding: 2rem;
        }
        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace;
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #ff3038;
        }
        .kicker-line {
            width: 1.5rem;
            height: 1px;
            background-color: #e50914;
        }
        .code {
            margin-top: 1rem;
            font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace;
            font-size: clamp(2.75rem, 8vw, 4rem);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #f5f5f5;
        }
        .title {
            margin-top: 0.75rem;
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }
        .message {
            margin-top: 0.75rem;
            font-size: 0.9375rem;
            line-height: 1.6;
            color: #a1a1aa;
        }
        .actions {
            margin-top: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
        }
        .btn:focus-visible {
            outline: 2px solid #ff3038;
            outline-offset: 2px;
        }
        .btn-primary {
            background-color: #e50914;
            border: 1px solid #e50914;
            color: #f5f5f5;
        }
        .btn-primary:hover {
            background-color: #b80710;
            border-color: #b80710;
        }
        .btn-secondary {
            background-color: #1e1e22;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f5f5f5;
        }
        .btn-secondary:hover {
            border-color: rgba(229, 9, 20, 0.6);
            color: #ff3038;
        }
        .footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.25rem;
            text-align: center;
            font-size: 0.75rem;
            color: #a1a1aa;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ url('/') }}" class="brand">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="brand-logo">
                @else
                    <span class="brand-initial" aria-hidden="true">{{ strtoupper(substr($appName, 0, 1)) }}</span>
                @endif
                <span>
                    <span class="brand-name" style="display: block;">{{ $appName }}</span>
                    <span class="brand-sub" style="display: block;">Top up game &amp; akun</span>
                </span>
            </a>
        </div>
    </header>

    <main class="main">
        <section class="card" aria-labelledby="error-title">
            <p class="kicker">
                <span class="kicker-line" aria-hidden="true"></span>
                Status @yield('code')
            </p>
            <p class="code">@yield('code')</p>
            <h1 id="error-title" class="title">@yield('title')</h1>
            <p class="message">@yield('message')</p>
            <div class="actions">
                <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke beranda</a>
                @hasSection('secondary_action')
                    @yield('secondary_action')
                @else
                    <a href="{{ url('/#katalog') }}" class="btn btn-secondary">Lihat katalog</a>
                @endif
            </div>
        </section>
    </main>

    <footer class="footer">
        &copy; {{ date('Y') }} {{ $appName }}. Semua hak dilindungi.
    </footer>
</body>
</html>
