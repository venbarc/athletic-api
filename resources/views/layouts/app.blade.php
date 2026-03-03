<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Atheletic API' }}</title>
    <style>
        :root {
            --bg: #eceef2;
            --nav: #2743a8;
            --nav-link: #d8e3ff;
            --card: #f4f5f7;
            --line: #cfd4de;
            --text: #2d3748;
            --muted: #5e6a7f;
            --active: #1f4fcc;
            --white: #fff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        .topbar {
            background: var(--nav);
            color: var(--white);
            border-bottom: 1px solid #1f3588;
        }

        .topbar-inner {
            width: min(1280px, 98vw);
            margin: 0 auto;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 8px;
        }

        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .brand {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.2px;
            line-height: 1;
        }

        .main-nav {
            display: inline-flex;
            gap: 18px;
            align-items: center;
            flex-wrap: wrap;
        }

        .main-nav a {
            color: var(--nav-link);
            text-decoration: none;
            font-size: 22px;
            line-height: 1;
            padding: 4px 0;
            border-bottom: 2px solid transparent;
        }

        .main-nav a.active {
            color: var(--white);
            border-color: var(--white);
        }

        .download-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .download-select {
            min-width: 290px;
            border-radius: 10px;
            border: 1px solid #17318a;
            background: #eef3ff;
            color: #213460;
            padding: 8px 10px;
            font-size: 15px;
            font-weight: 600;
        }

        .page {
            width: min(1220px, 96vw);
            margin: 26px auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 22px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .section-title {
            margin: 0;
            font-size: 34px;
            line-height: 1.1;
            color: #364153;
        }

        .section-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 16px;
        }

        .kpi-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .kpi {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px;
        }

        .kpi-label {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .kpi-value {
            margin-top: 4px;
            font-size: 28px;
            font-weight: 700;
            color: #2c3b54;
        }

        .endpoint-group {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #f0f2f5;
            padding: 12px;
        }

        .endpoint-group h3 {
            margin: 0 0 10px;
            color: #4b5567;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .endpoint-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .endpoint-btn {
            border: 1px solid #bbc3d0;
            border-radius: 8px;
            background: #f6f7f9;
            color: #3c4759;
            padding: 8px 12px;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .endpoint-btn.active {
            border-color: var(--active);
            background: #e8eeff;
            color: var(--active);
            font-weight: 600;
        }

        .endpoint-btn.not-ready {
            opacity: 0.58;
        }

        .endpoint-btn small {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .table-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .left-tools,
        .right-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .label {
            font-size: 16px;
            color: #3f4a5e;
        }

        .input,
        .select {
            border: 1px solid #b8c1cf;
            border-radius: 8px;
            background: #fff;
            padding: 8px 10px;
            min-width: 180px;
            font-size: 15px;
        }

        .btn {
            border: 1px solid #204ebf;
            border-radius: 8px;
            background: #e9efff;
            color: #1f4fcc;
            text-decoration: none;
            font-size: 16px;
            padding: 8px 12px;
            cursor: pointer;
        }

        .btn:hover { background: #dfe8ff; }

        .stats {
            margin-top: 12px;
            color: #5b6678;
            font-size: 14px;
        }

        .table-wrap {
            margin-top: 10px;
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #e4e8ee;
            vertical-align: top;
            white-space: nowrap;
        }

        th {
            color: #667287;
            font-weight: 600;
            text-transform: lowercase;
            letter-spacing: .2px;
        }

        td.payload {
            max-width: 460px;
            white-space: normal;
            color: #50607a;
        }

        .empty {
            margin-top: 14px;
            padding: 12px;
            border: 1px dashed #b8c1cf;
            border-radius: 10px;
            background: #fff;
            color: #5c687c;
        }

        .pager {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 1100px) {
            .brand { font-size: 26px; }
            .main-nav a { font-size: 18px; }
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .download-select { min-width: 220px; }
        }

        @media (max-width: 700px) {
            .topbar-inner { align-items: flex-start; }
            .section-title { font-size: 26px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .download-form { width: 100%; }
            .download-select { width: 100%; }
            .main-nav { gap: 10px; }
            .main-nav a { font-size: 16px; }
        }
    </style>
</head>
<body>
@php
    $exportGroups = $exportGroups ?? [];
    $selectedEndpointKey = $selectedEndpointKey ?? 'APPTS';
    $historyLink = route('pull-history.index', ['endpoint' => $selectedEndpointKey]);
    $apiLink = route('api.index', ['endpoint' => $selectedEndpointKey]);
@endphp

<header class="topbar">
    <div class="topbar-inner">
        <div class="brand-wrap">
            <div class="brand">{{ config('app.name', 'Atheletic API') }}</div>
            <nav class="main-nav" aria-label="Primary">
                <a href="{{ route('dashboard', ['endpoint' => $selectedEndpointKey]) }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ $historyLink }}" class="{{ request()->routeIs('pull-history.*') ? 'active' : '' }}">Pull History</a>
                <a href="{{ $apiLink }}" class="{{ request()->routeIs('api.index') ? 'active' : '' }}">API</a>
            </nav>
        </div>

        <form method="GET" action="{{ route('pull-history.export') }}" class="download-form">
            <select name="endpoint" class="download-select" onchange="if (this.value) { this.form.submit(); }">
                <option value="">Download CSV...</option>
                @foreach($exportGroups as $groupName => $endpoints)
                    <optgroup label="{{ $groupName }}">
                        @foreach($endpoints as $endpoint)
                            <option value="{{ $endpoint['key'] }}" @disabled(! $endpoint['ready'])>
                                {{ $endpoint['label'] }} CSV{{ $endpoint['ready'] ? '' : ' (Coming Soon)' }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            @foreach(request()->except('endpoint', 'page') as $param => $value)
                @if(! is_array($value))
                    <input type="hidden" name="{{ $param }}" value="{{ $value }}">
                @endif
            @endforeach
        </form>
    </div>
</header>

<main class="page">
    @yield('content')
</main>
</body>
</html>
