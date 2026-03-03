<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Athletic API' }}</title>
    <style>
        :root {
            --bg: #edf1f7;
            --nav: #1f44a5;
            --nav-2: #2a56c8;
            --nav-link: #dce7ff;
            --card: #ffffff;
            --line: #d6deea;
            --text: #263244;
            --muted: #637289;
            --active: #1f4fcc;
            --white: #fff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, #f3f6fb 0%, #ebf0f7 100%);
        }

        .topbar {
            background: linear-gradient(90deg, var(--nav) 0%, var(--nav-2) 100%);
            color: var(--white);
            border-bottom: 1px solid #17378f;
            box-shadow: 0 8px 24px rgba(19, 41, 105, 0.2);
        }

        .topbar-inner {
            width: min(1280px, 98vw);
            margin: 0 auto;
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 10px;
        }

        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .brand {
            font-size: 36px;
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
            font-size: 17px;
            font-weight: 600;
            line-height: 1;
            padding: 6px 0;
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
            min-width: 280px;
            border-radius: 9px;
            border: 1px solid #193b99;
            background: #eef3ff;
            color: #203663;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .page {
            width: min(1240px, 96vw);
            margin: 22px auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(22, 37, 69, 0.06);
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
            font-size: 50px;
            line-height: 1.06;
            color: #304158;
        }

        .section-subtitle {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 15px;
        }

        .kpi-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .kpi {
            background: #f9fbff;
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
            border-radius: 12px;
            background: #f7faff;
            padding: 12px;
        }

        .endpoint-group h3 {
            margin: 0 0 10px;
            color: #4b5870;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .endpoint-strip {
            padding: 12px 12px 10px;
        }

        .endpoint-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .endpoint-buttons-inline {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .endpoint-buttons-inline::-webkit-scrollbar {
            height: 6px;
        }

        .endpoint-buttons-inline::-webkit-scrollbar-thumb {
            background: #c8d3e6;
            border-radius: 6px;
        }

        .endpoint-btn {
            border: 1px solid #b8c4db;
            border-radius: 8px;
            background: #fff;
            color: #3b4960;
            padding: 7px 12px;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .endpoint-btn.active {
            border-color: var(--active);
            background: #e8eeff;
            color: var(--active);
            font-weight: 700;
        }

        .endpoint-btn.not-ready {
            opacity: 0.6;
        }

        .endpoint-btn small {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .table-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .left-tools,
        .right-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .label {
            font-size: 14px;
            color: #3f4a5e;
        }

        .input,
        .select {
            border: 1px solid #b8c1cf;
            border-radius: 8px;
            background: #fff;
            padding: 8px 10px;
            min-width: 180px;
            font-size: 14px;
        }

        .btn {
            border: 1px solid #204ebf;
            border-radius: 8px;
            background: #e9efff;
            color: #1f4fcc;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 12px;
            cursor: pointer;
        }

        .btn:hover { background: #dfe8ff; }

        .stats {
            margin-top: 12px;
            color: #5b6678;
            font-size: 13px;
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
            font-size: 14px;
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
            font-size: 13px;
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
            .brand { font-size: 30px; }
            .main-nav a { font-size: 15px; }
            .section-title { font-size: 38px; }
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .download-select { min-width: 220px; }
            .endpoint-buttons-inline { flex-wrap: wrap; overflow: visible; }
        }

        @media (max-width: 700px) {
            .topbar-inner { align-items: flex-start; }
            .section-title { font-size: 30px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .download-form { width: 100%; }
            .download-select { width: 100%; }
            .main-nav { gap: 10px; }
            .main-nav a { font-size: 14px; }
            .page { width: min(1240px, 98vw); }
        }
    </style>
</head>
<body>
@php
    $exportGroups = $exportGroups ?? [];
    $endpointCatalog = $endpointCatalog ?? [];

    if ($endpointCatalog === [] && $exportGroups !== []) {
        foreach ($exportGroups as $groupEndpoints) {
            foreach ($groupEndpoints as $endpoint) {
                $endpointCatalog[$endpoint['key']] = $endpoint;
            }
        }
    }

    $selectedEndpointKey = $selectedEndpointKey ?? 'APPTS';
    $historyLink = route('pull-history.index', ['endpoint' => $selectedEndpointKey]);
    $apiLink = route('api.index', ['endpoint' => $selectedEndpointKey]);
@endphp

<header class="topbar">
    <div class="topbar-inner">
        <div class="brand-wrap">
            <div class="brand">{{ config('app.name', 'Athletic API') }}</div>
            <nav class="main-nav" aria-label="Primary">
                <a href="{{ route('dashboard', ['endpoint' => $selectedEndpointKey]) }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ $historyLink }}" class="{{ request()->routeIs('pull-history.*') ? 'active' : '' }}">Pull History</a>
                <a href="{{ $apiLink }}" class="{{ request()->routeIs('api.index') ? 'active' : '' }}">API</a>
            </nav>
        </div>

        <form method="GET" action="{{ route('pull-history.export') }}" class="download-form">
            <select name="endpoint" class="download-select" onchange="if (this.value) { this.form.submit(); }">
                <option value="">Download CSV...</option>
                <optgroup label="API Endpoints">
                    @foreach($endpointCatalog as $endpoint)
                        <option value="{{ $endpoint['key'] }}" @disabled(! $endpoint['ready'])>
                            {{ $endpoint['label'] }} CSV{{ $endpoint['ready'] ? '' : ' (Coming Soon)' }}
                        </option>
                    @endforeach
                </optgroup>
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