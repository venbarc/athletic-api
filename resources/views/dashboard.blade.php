@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
<div class="card">
    <div class="row">
        <div>
            <h1 class="section-title">Provider Revenue (Atheletic API)</h1>
            <p class="section-subtitle">Switch between overview metrics and endpoint tables in one dashboard screen.</p>
        </div>
    </div>

    <div class="endpoint-buttons" style="margin-top:16px;">
        <a href="{{ route('dashboard', array_merge(request()->except('page'), ['endpoint' => $selectedEndpointKey, 'tab' => 'overview'])) }}"
           class="endpoint-btn {{ $activeTab === 'overview' ? 'active' : '' }}">
            Dashboard Overview
        </a>
        <a href="{{ route('dashboard', array_merge(request()->except('page'), ['endpoint' => $selectedEndpointKey, 'tab' => 'table'])) }}"
           class="endpoint-btn {{ $activeTab === 'table' ? 'active' : '' }}">
            API Table
        </a>
    </div>

    @if ($activeTab === 'overview')
        <div class="kpi-grid">
            <div class="kpi">
                <div class="kpi-label">Rows in Selected Table</div>
                <div class="kpi-value">{{ number_format((int) $metrics['total_lines']) }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Total Dump Files</div>
                <div class="kpi-value">{{ number_format((int) $metrics['total_dumps']) }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Processed Dumps</div>
                <div class="kpi-value">{{ number_format((int) $metrics['processed_dumps']) }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Failed Dumps</div>
                <div class="kpi-value">{{ number_format((int) $metrics['failed_dumps']) }}</div>
            </div>
        </div>

        <div class="stats">
            <div>Latest line update: <strong>{{ $metrics['latest_line_updated_at'] ?? 'N/A' }}</strong></div>
            <div>Latest completed pull: <strong>{{ $metrics['latest_pull_at'] ?? 'N/A' }}</strong></div>
            <div>Dump tracking table: <strong>{{ $dumpsTable }}</strong></div>
        </div>
    @else
        @foreach($endpointGroups as $groupName => $endpoints)
            <section class="endpoint-group">
                <h3>{{ $groupName }}</h3>
                <div class="endpoint-buttons">
                    @foreach($endpoints as $endpoint)
                        @php
                            $classes = 'endpoint-btn';
                            if ($endpoint['key'] === $selectedEndpointKey) {
                                $classes .= ' active';
                            }
                            if (! $endpoint['ready']) {
                                $classes .= ' not-ready';
                            }
                        @endphp
                        <a href="{{ route('dashboard', array_merge(request()->except('page'), ['endpoint' => $endpoint['key'], 'tab' => 'table'])) }}" class="{{ $classes }}">
                            {{ $endpoint['label'] }}
                            @if(! $endpoint['ready'])
                                <small>soon</small>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="table-tools">
            <div class="left-tools">
                <form method="GET" action="{{ route('dashboard') }}" class="left-tools">
                    <input type="hidden" name="endpoint" value="{{ $selectedEndpointKey }}">
                    <input type="hidden" name="tab" value="table">

                    <label class="label" for="per_page">Rows per page</label>
                    <select id="per_page" name="per_page" class="select" onchange="this.form.submit()">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($option === $perPage)>{{ $option }}</option>
                        @endforeach
                    </select>

                    <input type="text" name="q" class="input" value="{{ request('q') }}" placeholder="Search source ID, status, or ID">

                    @if (!empty($statusOptions))
                        <select name="status" class="select">
                            <option value="">All statuses</option>
                            @foreach($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    @endif

                    <button type="submit" class="btn">Apply</button>
                </form>
            </div>

            <div class="right-tools">
                <a class="btn" href="{{ route('pull-history.export', array_merge(request()->query(), ['endpoint' => $selectedEndpointKey])) }}">
                    Export {{ $selectedEndpoint['label'] }} CSV
                </a>
            </div>
        </div>

        @if (! $tableReady)
            <div class="empty">
                <strong>{{ $selectedEndpoint['label'] }}</strong> table is not available yet.
            </div>
        @elseif ($rows->count() === 0)
            <div class="empty">No rows found for current filters.</div>
        @else
            <div class="stats">Total rows: {{ number_format($rows->total()) }}</div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        @foreach($displayColumns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($displayColumns as $column)
                                @php $value = $row->{$column} ?? null; @endphp
                                <td class="{{ $column === 'payload' ? 'payload' : '' }}">
                                    {{ $column === 'payload' ? \Illuminate\Support\Str::limit((string) $value, 220) : ($value === null || $value === '' ? 'N/A' : $value) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pager">
                <div class="stats">Showing {{ $rows->firstItem() }}-{{ $rows->lastItem() }} of {{ number_format($rows->total()) }}</div>
                <div class="right-tools">
                    @if ($rows->onFirstPage())
                        <span class="btn" style="opacity:.5; pointer-events:none;">Previous</span>
                    @else
                        <a class="btn" href="{{ $rows->previousPageUrl() }}">Previous</a>
                    @endif

                    @if ($rows->hasMorePages())
                        <a class="btn" href="{{ $rows->nextPageUrl() }}">Next</a>
                    @else
                        <span class="btn" style="opacity:.5; pointer-events:none;">Next</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
