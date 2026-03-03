@extends('layouts.app', ['title' => 'API'])

@section('content')
<div class="card">
    <div class="row">
        <div>
            <h1 class="section-title">{{ $selectedEndpoint['label'] }} (Atheletic API)</h1>
            <p class="section-subtitle">Endpoint data viewer for available export endpoints.</p>
        </div>
    </div>

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
                    <a href="{{ route('api.index', array_merge(request()->except('page'), ['endpoint' => $endpoint['key']])) }}" class="{{ $classes }}">
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
            <form method="GET" action="{{ route('api.index') }}" class="left-tools">
                <input type="hidden" name="endpoint" value="{{ $selectedEndpointKey }}">

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
</div>
@endsection
