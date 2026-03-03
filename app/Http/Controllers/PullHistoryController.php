<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PullHistoryController extends Controller
{
    private const DEFAULT_ENDPOINT = 'APPTS';

    private const EXPORT_CHUNK_SIZE = 2000;

    /**
     * @var array<string, array<int, string>>
     */
    private const ENDPOINT_HEADERS = [
        'APPTS' => [
            'created_at',
            'updated_at',
            'id',
            'patient_id',
            'case_id',
            'rendering_provider_id',
            'referring_provider_id',
            'supervising_provider_id',
            'is_provider_info_from_ehr',
            'site_id',
            'facility_id',
            'from_date_range',
            'to_date_range',
            'prior_auth_required',
            'appointment_status',
            'chart_notes_attachment_url',
            'notes',
            'telemedicine_url',
            'service_type_codes',
            'is_specialist_visit',
            'tracking_tag_from_site_ehr',
            'ehr_source_identifier',
            'metadata_from_ehr',
            'cpt_codes',
            'created_by',
            'task_id',
            'reason',
            'expected_copay_in_usd_cents',
            'expected_co_insurance_percentage',
            'patient_outstanding_balance_in_usd_cents',
            'encounter_id',
            'primary_pii_id',
            'secondary_pii_id',
            'tertiary_pii_id',
            'wc_case_manager_name',
            'wc_case_manager_phone_number',
            'wc_case_manager_fax_number',
            'wc_date_of_injury',
            'wc_case_number',
            'specialty',
            'skip_collection_reason',
            'skip_collection_reason_updated_by',
            'skip_outstanding_balance_reason',
            'skip_outstanding_balance_updated_by',
            'prior_auth_id',
            'ehr_note_type',
            'primary_npi',
            'secondary_npi',
            'is_virtual',
            'checked_in_by_id',
            'checked_out_by_id',
            'colossus_missing_from_extraction_count',
            'colossus_latest_import',
            'medicare_part',
            'appointment_type',
            'appointments_updated_at',
            'ehr_appointment_types_updated_at',
        ],
        'ELIGIBILITY_CHECKS' => [
            'created_at',
            'updated_at',
            'id',
            'athelas_task_id',
            'cmds_task_id',
            'raw_request',
            'raw_response_url',
            'check_source',
            'has_error',
            'created_by',
            'date_of_service',
            'parsed_data_url',
            'parsed_data',
            'oon_parsed_data',
            'suggested_copay_amt',
            'is_deductible_met',
            'is_in_network',
            'plan_info',
            'service_type_codes',
            'is_proactive_visit',
            'is_archived',
            'failed_to_parse_response_reason',
            'payer_plan_info',
            'medicare_part',
            'waystar_inquiry_id',
            'created_by_user_id',
            'appointment_id',
            'patient_insurance_info_id',
            'site_id',
            'facility_id',
            'eligibility_coverage_id',
            'mapped_insurance_company_id',
            'patient_id',
            'eligibility_checks_updated_at',
            'patients_updated_at',
        ],
        'PRIOR_AUTHS' => [
            'created_at',
            'updated_at',
            'id',
            'prior_authorization_status',
            'patient_id',
            'appointment_id',
            'insurance_company_id',
            'insurance_priority',
            'prior_authorization_number',
            'mode_of_request',
            'reason',
            'notes',
            'prior_authorization_result',
            'expiration_date',
            'effective_date',
            'prior_auth_requested_id',
            'icd_10_codes',
            'cpt_codes',
            'visits_total',
            'visits_remaining',
            'prior_auth_type',
            'medical_records_attachment_url',
            'urgency',
            'encounter_id',
            'patient_insurance_info_id',
            'is_archived',
            'total_billable_units',
            'remaining_billable_units',
            'prior_auth_category',
            'referring_provider_id',
            'encounter_visits_total',
            'encounter_visits_remaining',
            'referring_facility_id',
            'prior_auth_tracking_type',
        ],
    ];

    public function dashboard(Request $request): View
    {
        $context = $this->endpointContext($request);
        $selectedEndpoint = $context['selectedEndpoint'];
        $selectedEndpointKey = $context['selectedEndpointKey'];
        $dumpsTable = $this->resolveDumpsTable();
        $activeTab = strtolower((string) $request->query('tab', 'overview'));

        if (! in_array($activeTab, ['overview', 'table'], true)) {
            $activeTab = 'overview';
        }

        $metrics = [
            'total_lines' => 0,
            'latest_line_updated_at' => null,
            'total_dumps' => 0,
            'processed_dumps' => 0,
            'failed_dumps' => 0,
            'latest_pull_at' => null,
        ];

        if ($selectedEndpoint['ready']) {
            $metrics['total_lines'] = DB::table($selectedEndpoint['table'])->count();

            if (Schema::hasColumn($selectedEndpoint['table'], 'source_updated_at')) {
                $metrics['latest_line_updated_at'] = DB::table($selectedEndpoint['table'])->max('source_updated_at');
            }
        }

        if (Schema::hasTable($dumpsTable)) {
            $metrics['total_dumps'] = DB::table($dumpsTable)
                ->where('dump_type', $selectedEndpointKey)
                ->count();
            $metrics['processed_dumps'] = DB::table($dumpsTable)
                ->where('dump_type', $selectedEndpointKey)
                ->where('status', 'processed')
                ->count();
            $metrics['failed_dumps'] = DB::table($dumpsTable)
                ->where('dump_type', $selectedEndpointKey)
                ->where('status', 'failed')
                ->count();
            $metrics['latest_pull_at'] = DB::table($dumpsTable)
                ->where('dump_type', $selectedEndpointKey)
                ->max('processed_at');
        }

        $tableState = $activeTab === 'table'
            ? $this->apiTableState($request, $selectedEndpoint)
            : $this->emptyApiTableState($request);

        return view('dashboard', [
            ...$context,
            ...$tableState,
            'metrics' => $metrics,
            'dumpsTable' => $dumpsTable,
            'activeTab' => $activeTab,
        ]);
    }

    public function history(Request $request): View
    {
        $context = $this->endpointContext($request);
        $selectedEndpointKey = $context['selectedEndpointKey'];
        $dumpsTable = $this->resolveDumpsTable();
        $perPage = $this->resolvePerPage($request);

        if (! Schema::hasTable($dumpsTable)) {
            return view('pull-history.index', [
                ...$context,
                'tableReady' => false,
                'rows' => $this->emptyPaginator($perPage, $request),
                'perPage' => $perPage,
                'perPageOptions' => [25, 50, 100, 250],
                'totalRuns' => 0,
            ]);
        }

        $query = DB::table($dumpsTable)
            ->where('dump_type', $selectedEndpointKey)
            ->orderByDesc('id');

        $rows = $query
            ->paginate($perPage)
            ->withQueryString();

        return view('pull-history.index', [
            ...$context,
            'tableReady' => true,
            'rows' => $rows,
            'perPage' => $perPage,
            'perPageOptions' => [25, 50, 100, 250],
            'totalRuns' => DB::table($dumpsTable)->where('dump_type', $selectedEndpointKey)->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $context = $this->endpointContext($request);
        $tableState = $this->apiTableState($request, $context['selectedEndpoint']);

        return view('api.index', [
            ...$context,
            ...$tableState,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $context = $this->endpointContext($request);
        $selectedEndpoint = $context['selectedEndpoint'];
        $selectedEndpointKey = $context['selectedEndpointKey'];

        if (! $selectedEndpoint['ready']) {
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'w');
                fputcsv($output, ['message']);
                fputcsv($output, ['Selected endpoint table is not available yet.']);
                fclose($output);
            }, 'pull-history-empty.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $table = $selectedEndpoint['table'];
        $allColumns = Schema::getColumnListing($table);
        $query = $this->buildApiDataQuery($request, $table, $allColumns);
        $filename = strtolower($selectedEndpointKey).'-pull-data-'.now()->format('Ymd_His').'.csv';

        if ($this->hasFixedHeaders($selectedEndpointKey)) {
            return $this->exportWithFixedHeaders($selectedEndpointKey, $query, $allColumns, $filename);
        }

        return $this->exportGeneric($query, $allColumns, $filename);
    }

    private function exportGeneric(Builder $baseQuery, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($baseQuery, $columns): void {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $output = fopen('php://output', 'w');
            fputcsv($output, $columns);

            if (in_array('id', $columns, true)) {
                $query = clone $baseQuery;
                $query->select($columns)->orderBy('id');

                $query->chunkById(self::EXPORT_CHUNK_SIZE, function ($rows) use ($output, $columns): void {
                    foreach ($rows as $row) {
                        $line = [];

                        foreach ($columns as $column) {
                            $line[] = $row->{$column} ?? null;
                        }

                        fputcsv($output, $line);
                    }

                    fflush($output);
                }, 'id');
            } else {
                $query = clone $baseQuery;

                foreach ($query->select($columns)->cursor() as $row) {
                    $line = [];

                    foreach ($columns as $column) {
                        $line[] = $row->{$column} ?? null;
                    }

                    fputcsv($output, $line);
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function exportWithFixedHeaders(string $endpointKey, Builder $baseQuery, array $allColumns, string $filename): StreamedResponse
    {
        $headers = $this->fixedHeaders($endpointKey);
        $selectExpressions = $this->buildEndpointSelectExpressions($endpointKey, $headers, $allColumns);
        $hasPayload = in_array('payload', $allColumns, true);

        return response()->streamDownload(function () use ($baseQuery, $headers, $selectExpressions, $hasPayload, $allColumns): void {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            $query = clone $baseQuery;
            $query->select($selectExpressions);

            if ($hasPayload) {
                $query->addSelect('payload');
            }

            if (in_array('id', $allColumns, true)) {
                $query->addSelect(DB::raw('`id` as `__chunk_id`'));
                $query->orderBy('id');

                $query->chunkById(self::EXPORT_CHUNK_SIZE, function ($rows) use ($output, $headers, $hasPayload): void {
                    foreach ($rows as $row) {
                        $payload = $hasPayload ? $this->decodePayload($row->payload ?? null) : [];
                        $line = [];

                        foreach ($headers as $header) {
                            $value = $row->{$header} ?? null;

                            if (($value === null || $value === '') && array_key_exists($header, $payload)) {
                                $value = $payload[$header];
                            }

                            $line[] = $value;
                        }

                        fputcsv($output, $line);
                    }

                    fflush($output);
                }, 'id', '__chunk_id');
            } else {
                foreach ($query->cursor() as $row) {
                    $payload = $hasPayload ? $this->decodePayload($row->payload ?? null) : [];
                    $line = [];

                    foreach ($headers as $header) {
                        $value = $row->{$header} ?? null;

                        if (($value === null || $value === '') && array_key_exists($header, $payload)) {
                            $value = $payload[$header];
                        }

                        $line[] = $value;
                    }

                    fputcsv($output, $line);
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $allColumns
     * @return array<int, \Illuminate\Database\Query\Expression>
     */
    private function buildEndpointSelectExpressions(string $endpointKey, array $headers, array $allColumns): array
    {
        $columnLookup = array_flip($allColumns);
        $expressions = [];

        foreach ($headers as $header) {
            $dbColumn = $this->endpointHeaderToDbColumn($endpointKey, $header);

            if (isset($columnLookup[$dbColumn])) {
                $expressions[] = DB::raw("`{$dbColumn}` as `{$header}`");
                continue;
            }

            $expressions[] = DB::raw("NULL as `{$header}`");
        }

        return $expressions;
    }

    private function endpointHeaderToDbColumn(string $endpointKey, string $header): string
    {
        if ($this->hasFixedHeaders($endpointKey)) {
            return match ($header) {
                'id' => 'payload_id',
                'created_at' => 'payload_created_at',
                'updated_at' => 'payload_updated_at',
                default => $header,
            };
        }

        return $header;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(mixed $payload): array
    {
        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function endpointContext(Request $request): array
    {
        $endpointCatalog = $this->endpointCatalog();
        $selectedEndpointKey = strtoupper((string) $request->query('endpoint', self::DEFAULT_ENDPOINT));

        if (! array_key_exists($selectedEndpointKey, $endpointCatalog)) {
            $selectedEndpointKey = self::DEFAULT_ENDPOINT;
        }

        $selectedEndpoint = $endpointCatalog[$selectedEndpointKey] ?? reset($endpointCatalog);

        return [
            'endpointCatalog' => $endpointCatalog,
            'endpointGroups' => $this->groupedEndpoints($endpointCatalog),
            'exportGroups' => $this->groupedEndpoints($endpointCatalog),
            'selectedEndpoint' => $selectedEndpoint,
            'selectedEndpointKey' => $selectedEndpointKey,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function endpointCatalog(): array
    {
        $catalog = [];

        foreach ($this->endpointDefinitions() as $key => $definition) {
            $table = $this->resolveFirstExistingTable($definition['tables']);

            $catalog[$key] = [
                'key' => $key,
                'label' => $definition['label'],
                'group' => $definition['group'],
                'table' => $table,
                'ready' => $table !== null,
            ];
        }

        return $catalog;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupedEndpoints(array $catalog): array
    {
        $groups = [];

        foreach ($catalog as $endpoint) {
            $groups[$endpoint['group']][] = $endpoint;
        }

        return $groups;
    }

    /**
     * @return array<string, array{label: string, group: string, tables: array<int, string>}>
     */
    private function endpointDefinitions(): array
    {
        return [
            'APPTS' => [
                'label' => 'Available Blocks',
                'group' => 'API Endpoints',
                'tables' => ['athletic_appts', 'athelas_appts'],
            ],
            'ELIGIBILITY_CHECKS' => [
                'label' => 'Eligibility Checks',
                'group' => 'API Endpoints',
                'tables' => ['athletic_eligibility_checks', 'athelas_eligibility_checks'],
            ],
            'PRIOR_AUTHS' => [
                'label' => 'Prior Auths',
                'group' => 'API Endpoints',
                'tables' => ['athletic_prior_auths', 'athelas_prior_auths'],
            ],
        ];
    }

    private function resolveFirstExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (Schema::hasTable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $allColumns
     * @return array<int, string>
     */
    private function resolveDisplayColumns(array $allColumns, string $endpointKey): array
    {
        if ($this->hasFixedHeaders($endpointKey)) {
            return $this->fixedHeaders($endpointKey);
        }

        $preferred = [
            'id',
            'source_id',
            'dump_id',
            'source_updated_at',
            'from_datetime',
            'to_datetime',
            'created_at',
            'payload',
        ];

        $display = array_values(array_intersect($preferred, $allColumns));

        if ($display === []) {
            $display = array_slice($allColumns, 0, 8);
        }

        return $display;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function buildApiDataQuery(Request $request, string $table, array $columns): Builder
    {
        $query = DB::table($table);

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $searchColumns = array_values(array_intersect([
                'source_id',
                'payload_id',
                'appointment_status',
                'prior_authorization_status',
                'dump_id',
                'id',
            ], $columns));

            if ($searchColumns === []) {
                $searchColumns = array_slice($columns, 0, 4);
            }

            $query->where(function (Builder $builder) use ($search, $searchColumns): void {
                foreach ($searchColumns as $column) {
                    if (in_array($column, ['dump_id', 'id'], true) && ctype_digit($search)) {
                        $builder->orWhere($column, (int) $search);
                        continue;
                    }

                    $builder->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $status = trim((string) $request->query('status', ''));
        $statusColumn = null;

        foreach (['appointment_status', 'prior_authorization_status'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $statusColumn = $candidate;
                break;
            }
        }

        if ($status !== '' && $statusColumn !== null) {
            $query->where($statusColumn, $status);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $selectedEndpoint
     * @return array<string, mixed>
     */
    private function apiTableState(Request $request, array $selectedEndpoint): array
    {
        $perPage = $this->resolvePerPage($request);

        if (! $selectedEndpoint['ready']) {
            return [
                'rows' => $this->emptyPaginator($perPage, $request),
                'tableReady' => false,
                'displayColumns' => [],
                'statusOptions' => [],
                'perPage' => $perPage,
                'perPageOptions' => [25, 50, 100, 250],
            ];
        }

        $table = $selectedEndpoint['table'];
        $allColumns = Schema::getColumnListing($table);
        $endpointKey = (string) ($selectedEndpoint['key'] ?? '');
        $displayColumns = $this->resolveDisplayColumns($allColumns, $endpointKey);

        $query = $this->buildApiDataQuery($request, $table, $allColumns);

        $orderColumn = in_array('id', $allColumns, true)
            ? 'id'
            : ($allColumns[0] ?? null);

        if ($orderColumn !== null) {
            $query->orderByDesc($orderColumn);
        }

        if ($this->hasFixedHeaders($endpointKey)) {
            $select = $this->buildEndpointSelectExpressions($endpointKey, $displayColumns, $allColumns);
        } else {
            $select = $displayColumns;
        }

        $rows = $query
            ->select($select)
            ->paginate($perPage)
            ->withQueryString();

        $statusOptions = [];

        foreach (['appointment_status', 'prior_authorization_status'] as $statusColumn) {
            if (! in_array($statusColumn, $allColumns, true)) {
                continue;
            }

            $statusOptions = DB::table($table)
                ->whereNotNull($statusColumn)
                ->where($statusColumn, '!=', '')
                ->distinct()
                ->orderBy($statusColumn)
                ->limit(150)
                ->pluck($statusColumn)
                ->all();

            if ($statusOptions !== []) {
                break;
            }
        }

        return [
            'rows' => $rows,
            'tableReady' => true,
            'displayColumns' => $displayColumns,
            'statusOptions' => $statusOptions,
            'perPage' => $perPage,
            'perPageOptions' => [25, 50, 100, 250],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyApiTableState(Request $request): array
    {
        $perPage = $this->resolvePerPage($request);

        return [
            'rows' => $this->emptyPaginator($perPage, $request),
            'tableReady' => false,
            'displayColumns' => [],
            'statusOptions' => [],
            'perPage' => $perPage,
            'perPageOptions' => [25, 50, 100, 250],
        ];
    }

    private function resolvePerPage(Request $request): int
    {
        $allowed = [25, 50, 100, 250];
        $requested = (int) $request->query('per_page', 50);

        return in_array($requested, $allowed, true) ? $requested : 50;
    }

    private function emptyPaginator(int $perPage, Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            1,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function resolveDumpsTable(): string
    {
        if (Schema::hasTable('athletic_data_dumps')) {
            return 'athletic_data_dumps';
        }

        if (Schema::hasTable('athelas_data_dumps')) {
            return 'athelas_data_dumps';
        }

        return 'athletic_data_dumps';
    }

    private function hasFixedHeaders(string $endpointKey): bool
    {
        return array_key_exists($endpointKey, self::ENDPOINT_HEADERS);
    }

    /**
     * @return array<int, string>
     */
    private function fixedHeaders(string $endpointKey): array
    {
        return self::ENDPOINT_HEADERS[$endpointKey] ?? [];
    }

    public function statusLabel(string $status, int $rowsUpserted): string
    {
        return match ($status) {
            'processed' => 'SUCCESS',
            'failed' => $rowsUpserted > 0 ? 'PARTIAL' : 'FAILED',
            'processing' => 'RUNNING',
            default => strtoupper($status),
        };
    }

    public function durationSeconds(?string $start, ?string $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        try {
            return Carbon::parse($start)->diffInSeconds(Carbon::parse($end));
        } catch (\Throwable) {
            return null;
        }
    }
}