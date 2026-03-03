@extends('layouts.app', ['title' => 'Pull History'])

@section('content')
<style>
    .history-card {
        background: #f4f5f7;
        border: 1px solid #cfd4de;
        border-radius: 12px;
        padding: 20px 24px;
    }

    .history-headline {
        margin: 0 0 6px;
        font-size: 38px;
        color: #2f3c52;
        line-height: 1.1;
    }

    .history-divider {
        border: 0;
        border-top: 1px solid #cfd4de;
        margin: 10px 0 16px;
    }

    .history-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .history-total {
        color: #56657d;
        font-size: 16px;
    }

    .history-per-page {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #2d3748;
        font-size: 30px;
    }

    .history-select {
        border: 1px solid #9ca8bd;
        border-radius: 4px;
        padding: 4px 6px;
        background: #fff;
        min-width: 70px;
        font-size: 24px;
    }

    .history-table-wrap {
        margin-top: 8px;
        overflow: auto;
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1260px;
        font-size: 14px;
    }

    .history-table th,
    .history-table td {
        border-bottom: 1px solid #dbe0e8;
        padding: 12px 8px;
        text-align: left;
        vertical-align: top;
    }

    .history-table th {
        color: #4b5b73;
        font-weight: 600;
        font-size: 31px;
    }

    .history-table td {
        color: #233249;
        font-size: 28px;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 22px;
        line-height: 1;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .status-success {
        background: #b8f5cf;
        color: #0f6a3d;
    }

    .status-partial {
        background: #f4dfac;
        color: #9e6a08;
    }

    .status-failed {
        background: #f8c4c8;
        color: #9f1c2e;
    }

    .status-running {
        background: #cce1ff;
        color: #1f4fcc;
    }

    .status-neutral {
        background: #e4e7ee;
        color: #4a5568;
    }

    .history-error {
        max-width: 320px;
        white-space: normal;
        color: #a01f32;
    }

    .history-pager {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media (max-width: 1200px) {
        .history-headline {
            font-size: 30px;
        }

        .history-table th {
            font-size: 23px;
        }

        .history-table td {
            font-size: 20px;
        }

        .history-per-page {
            font-size: 22px;
        }

        .history-select {
            font-size: 18px;
        }

        .status-pill {
            font-size: 17px;
        }
    }

    @media (max-width: 700px) {
        .history-card {
            padding: 16px;
        }

        .history-headline {
            font-size: 26px;
        }

        .history-table th {
            font-size: 17px;
        }

        .history-table td {
            font-size: 15px;
        }

        .history-per-page {
            font-size: 17px;
        }

        .history-select {
            font-size: 15px;
        }

        .status-pill {
            font-size: 13px;
        }
    }
</style>

<section class="history-card">
    <h1 class="history-headline">Pull History</h1>
    <hr class="history-divider">

    <div class="history-meta">
        <div class="history-total">Total runs: {{ number_format((int) $totalRuns) }}</div>

        <form method="GET" action="{{ route('pull-history.index') }}" class="history-per-page">
            <input type="hidden" name="endpoint" value="{{ $selectedEndpointKey }}">
            <label for="per_page">Rows per page</label>
            <select id="per_page" name="per_page" class="history-select" onchange="this.form.submit()">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($option === $perPage)>{{ $option }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if (! $tableReady)
        <div class="empty">Pull history table is not available yet.</div>
    @elseif ($rows->count() === 0)
        <div class="empty">No pull history rows found.</div>
    @else
        <div class="history-table-wrap">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Command</th>
                        <th>Status</th>
                        <th>Triggered By</th>
                        <th>Started</th>
                        <th>Completed</th>
                        <th>Duration (s)</th>
                        <th>Fetched</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Upserted</th>
                        <th>Failed Chunks</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $statusLabel = match($row->status) {
                                'processed' => 'SUCCESS',
                                'failed' => ((int) $row->rows_upserted > 0 ? 'PARTIAL' : 'FAILED'),
                                'processing' => 'RUNNING',
                                default => strtoupper((string) $row->status),
                            };

                            $statusClass = match($statusLabel) {
                                'SUCCESS' => 'status-success',
                                'PARTIAL' => 'status-partial',
                                'FAILED' => 'status-failed',
                                'RUNNING' => 'status-running',
                                default => 'status-neutral',
                            };

                            $startedAt = $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at) : null;
                            $completedAt = $row->processed_at ? \Illuminate\Support\Carbon::parse($row->processed_at) : null;
                            $duration = ($startedAt && $completedAt) ? $startedAt->diffInSeconds($completedAt) : null;
                        @endphp
                        <tr>
                            <td>atheletic:sync-appts</td>
                            <td><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>scheduler</td>
                            <td>{{ $startedAt ? $startedAt->format('Y-m-d H:i:s') : '-' }}</td>
                            <td>{{ $completedAt ? $completedAt->format('Y-m-d H:i:s') : '-' }}</td>
                            <td>{{ $duration ?? '-' }}</td>
                            <td>{{ number_format((int) $row->rows_upserted) }}</td>
                            <td>0</td>
                            <td>{{ number_format((int) $row->rows_upserted) }}</td>
                            <td>{{ number_format((int) $row->rows_upserted) }}</td>
                            <td>{{ $row->status === 'failed' && (int) $row->rows_upserted === 0 ? '1' : '-' }}</td>
                            <td class="history-error">
                                {{ $row->last_error ? \Illuminate\Support\Str::limit((string) $row->last_error, 120) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="history-pager">
            <div class="history-total">
                Showing {{ $rows->firstItem() }}-{{ $rows->lastItem() }} of {{ number_format($rows->total()) }}
            </div>
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
</section>
@endsection
