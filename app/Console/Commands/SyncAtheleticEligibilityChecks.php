<?php

namespace App\Console\Commands;

use App\Services\Atheletic\AtheleticApiClient;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileObject;
use Throwable;

class SyncAtheleticEligibilityChecks extends Command
{
    private const TABLE = 'atheletic_eligibility_checks';

    private const DUMP_TYPE = 'ELIGIBILITY_CHECKS';

    protected $signature = 'atheletic:sync-eligibility-checks
        {--dump-id=* : Process only specific dump IDs}
        {--reprocess : Process dumps even if already marked as processed}';

    protected $description = 'Pull ELIGIBILITY_CHECKS dump files from Atheletic and upsert them locally in chunked batches.';

    public function handle(): int
    {
        $lock = Cache::lock('atheletic:sync-eligibility-checks', 3600);

        if (! $lock->get()) {
            $this->warn('Sync already running. Skipping this run.');

            return self::SUCCESS;
        }

        try {
            $chunkSize = $this->resolveChunkSize();
            $this->info("Starting ELIGIBILITY_CHECKS sync (automatic chunk size: {$chunkSize}).");

            try {
                $client = AtheleticApiClient::fromConfig();
                $dumps = $client->getDataDumpsList(self::DUMP_TYPE);
            } catch (Throwable $e) {
                $this->error('Failed to load ELIGIBILITY_CHECKS dump list: '.$e->getMessage());

                return self::FAILURE;
            }

            if ($dumps === []) {
                $this->info('No ELIGIBILITY_CHECKS dumps returned by upstream.');

                return self::SUCCESS;
            }

            $requestedDumpIds = collect($this->option('dump-id'))
                ->map(fn (mixed $value): int => (int) $value)
                ->filter(fn (int $value): bool => $value > 0)
                ->unique()
                ->values();

            if ($requestedDumpIds->isNotEmpty()) {
                $dumps = array_values(array_filter(
                    $dumps,
                    fn (array $dump): bool => $requestedDumpIds->contains((int) data_get($dump, 'id', 0))
                ));
            }

            if ($dumps === []) {
                $this->warn('No dumps matched the given filters.');

                return self::SUCCESS;
            }

            usort($dumps, function (array $left, array $right): int {
                $leftTime = (string) data_get($left, 'to_datetime', data_get($left, 'from_datetime', ''));
                $rightTime = (string) data_get($right, 'to_datetime', data_get($right, 'from_datetime', ''));

                if ($leftTime !== $rightTime) {
                    return $leftTime <=> $rightTime;
                }

                return ((int) data_get($left, 'id', 0)) <=> ((int) data_get($right, 'id', 0));
            });

            $shouldReprocess = (bool) $this->option('reprocess');
            $processedLookup = $this->processedDumpLookup($shouldReprocess);
            $processedDumps = 0;
            $totalRows = 0;

            foreach ($dumps as $dump) {
                $dumpId = (int) data_get($dump, 'id', 0);

                if ($dumpId <= 0) {
                    $this->warn('Skipping a dump entry with invalid id.');
                    continue;
                }

                if (! $shouldReprocess && isset($processedLookup[$dumpId])) {
                    $this->line("Skipping dump {$dumpId} (already processed).");
                    continue;
                }

                $this->line("Processing dump {$dumpId}...");

                try {
                    $rows = $this->processDump($client, $dump, $chunkSize);
                    $this->markDumpProcessed($dump, $rows);

                    $processedDumps++;
                    $totalRows += $rows;
                    $processedLookup[$dumpId] = true;

                    $this->info("Dump {$dumpId} complete. Rows upserted: {$rows}.");
                } catch (Throwable $e) {
                    $this->markDumpFailed($dump, $e->getMessage());
                    $this->error("Dump {$dumpId} failed: ".$e->getMessage());
                }
            }

            $this->info("ELIGIBILITY_CHECKS sync done. Dumps processed: {$processedDumps}. Rows upserted: {$totalRows}.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function resolveChunkSize(): int
    {
        $configured = (int) config('services.atheletic.eligibility_checks_chunk_size', 200);

        if ($configured <= 0) {
            throw new RuntimeException('Chunk size must be greater than zero.');
        }

        return min(max($configured, 50), 500);
    }

    /**
     * @param  array<string, mixed>  $dump
     */
    private function processDump(AtheleticApiClient $client, array $dump, int $chunkSize): int
    {
        $dumpId = (int) data_get($dump, 'id', 0);
        $dumpFilePath = data_get($dump, 'file_path');

        $this->markDumpProcessing($dump);

        $workingDir = storage_path('app/private/atheletic/eligibility_checks');
        File::ensureDirectoryExists($workingDir);

        $csvPath = "{$workingDir}/dump-{$dumpId}.csv";
        $client->downloadDumpFile(
            $dumpId,
            $csvPath,
            is_string($dumpFilePath) ? $dumpFilePath : null
        );

        $headers = $this->readCsvHeaders($csvPath);

        if ($headers === []) {
            throw new RuntimeException("Dump {$dumpId} CSV header row is empty.");
        }

        $payloadColumnMap = $this->resolvePayloadColumnMap($headers);
        $this->ensureColumnsExist(array_values($payloadColumnMap));

        $fromDatetime = $this->parseNullableDatetime(data_get($dump, 'from_datetime'));
        $toDatetime = $this->parseNullableDatetime(data_get($dump, 'to_datetime'));

        $now = now()->format('Y-m-d H:i:s');
        $rowsUpserted = 0;
        $batch = [];

        try {
            foreach ($this->readCsvRows($csvPath, $headers) as $row) {
                $payload = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                if ($payload === false) {
                    throw new RuntimeException("Unable to encode ELIGIBILITY_CHECKS row from dump {$dumpId} as JSON.");
                }

                $payloadColumnsData = [];

                foreach ($payloadColumnMap as $payloadKey => $columnName) {
                    $payloadColumnsData[$columnName] = $row[$payloadKey] ?? null;
                }

                $batch[] = array_merge([
                    'source_id' => $this->resolveSourceId($row),
                    'dump_id' => $dumpId,
                    'from_datetime' => $fromDatetime,
                    'to_datetime' => $toDatetime,
                    'source_updated_at' => $this->extractSourceUpdatedAt($row),
                    'payload' => $payload,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $payloadColumnsData);

                if (count($batch) < $chunkSize) {
                    continue;
                }

                $rowsUpserted = $this->flushBatch($dumpId, $batch, $rowsUpserted, array_values($payloadColumnMap));
            }

            if ($batch !== []) {
                $rowsUpserted = $this->flushBatch($dumpId, $batch, $rowsUpserted, array_values($payloadColumnMap));
            }
        } finally {
            File::delete($csvPath);
        }

        return $rowsUpserted;
    }

    /**
     * @return array<int, string>
     */
    private function readCsvHeaders(string $csvPath): array
    {
        if (! File::exists($csvPath)) {
            throw new RuntimeException("CSV file not found at path: {$csvPath}");
        }

        $file = new SplFileObject($csvPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            return $this->normalizeHeaders($row);
        }

        return [];
    }

    /**
     * @param  array<int, string>  $headers
     * @return \Generator<int, array<string, string|null>>
     */
    private function readCsvRows(string $csvPath, array $headers): \Generator
    {
        if (! File::exists($csvPath)) {
            throw new RuntimeException("CSV file not found at path: {$csvPath}");
        }

        if ($headers === []) {
            return;
        }

        $file = new SplFileObject($csvPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $skippedHeader = false;

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if (! $skippedHeader) {
                $skippedHeader = true;
                continue;
            }

            $normalizedRow = array_pad($row, count($headers), null);
            $assoc = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $value = $normalizedRow[$index] ?? null;

                if (is_string($value)) {
                    $value = trim($value);
                }

                $assoc[$header] = ($value === '') ? null : $value;
            }

            if ($assoc !== []) {
                yield $assoc;
            }
        }
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function (mixed $header): string {
            $normalized = (string) $header;
            $normalized = ltrim($normalized, "\xEF\xBB\xBF");
            $normalized = Str::of($normalized)
                ->trim()
                ->lower()
                ->replace([' ', '-'], '_')
                ->replaceMatches('/[^a-z0-9_]/', '')
                ->value();

            return $normalized;
        }, $headers);
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, string>
     */
    private function resolvePayloadColumnMap(array $headers): array
    {
        $map = [];
        $usedColumns = [];

        foreach ($headers as $header) {
            if ($header === '') {
                continue;
            }

            $column = $this->mapPayloadHeaderToColumn($header);
            $base = $column;
            $suffix = 1;

            while (isset($usedColumns[$column])) {
                $candidate = $base.'_'.$suffix;

                if (strlen($candidate) > 64) {
                    $candidate = substr($base, 0, max(1, 64 - strlen((string) $suffix) - 1)).'_'.$suffix;
                }

                $column = $candidate;
                $suffix++;
            }

            $usedColumns[$column] = true;
            $map[$header] = $column;
        }

        return $map;
    }

    private function mapPayloadHeaderToColumn(string $header): string
    {
        $column = match ($header) {
            'id' => 'payload_id',
            'created_at' => 'payload_created_at',
            'updated_at' => 'payload_updated_at',
            default => $header,
        };

        if (strlen($column) <= 64) {
            return $column;
        }

        $hash = substr(sha1($column), 0, 8);

        return substr($column, 0, 55).'_'.$hash;
    }

    /**
     * @param array<int, string> $columnNames
     */
    private function ensureColumnsExist(array $columnNames): void
    {
        $columnNames = array_values(array_unique(array_filter($columnNames, fn (mixed $name): bool => is_string($name) && $name !== '')));

        if ($columnNames === []) {
            return;
        }

        $tableName = Schema::hasTable(self::TABLE)
            ? self::TABLE
            : (Schema::hasTable('athelas_eligibility_checks') ? 'athelas_eligibility_checks' : null);

        if ($tableName === null) {
            throw new RuntimeException('ELIGIBILITY_CHECKS table does not exist. Run migrations first.');
        }

        $existing = array_flip(Schema::getColumnListing($tableName));
        $missing = array_values(array_filter($columnNames, fn (string $name): bool => ! isset($existing[$name])));

        if ($missing === []) {
            return;
        }

        $anchorColumn = Schema::hasColumn($tableName, 'patients_updated_at')
            ? 'patients_updated_at'
            : (Schema::hasColumn($tableName, 'updated_at') ? 'updated_at' : 'source_updated_at');

        Schema::table($tableName, function (Blueprint $table) use ($missing, &$anchorColumn): void {
            foreach ($missing as $column) {
                $definition = $table->longText($column)->nullable();

                if (method_exists($definition, 'after') && $anchorColumn !== '') {
                    $definition->after($anchorColumn);
                }

                $anchorColumn = $column;
            }
        });
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function resolveSourceId(array $row): string
    {
        foreach (['id', 'eligibility_check_id', 'external_id'] as $candidate) {
            $value = $row[$candidate] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $fallback = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($fallback === false || $fallback === '') {
            return Str::uuid()->toString();
        }

        return hash('sha256', $fallback);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function extractSourceUpdatedAt(array $row): ?string
    {
        foreach (['eligibility_checks_updated_at', 'updated_at', 'created_at'] as $candidate) {
            $value = $row[$candidate] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $parsed = $this->parseNullableDatetime($value);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function parseNullableDatetime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->utc()->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $payloadColumns
     */
    private function upsertBatch(array $rows, array $payloadColumns): void
    {
        $updateColumns = array_values(array_unique(array_merge([
            'dump_id',
            'from_datetime',
            'to_datetime',
            'source_updated_at',
            'payload',
            'updated_at',
        ], $payloadColumns)));

        DB::table(self::TABLE)->upsert(
            $rows,
            ['source_id'],
            $updateColumns
        );
    }

    /**
     * @param  array<string, mixed>  $dump
     */
    private function markDumpProcessing(array $dump): void
    {
        $this->upsertDumpState($dump, [
            'status' => 'processing',
            'processed_at' => null,
            'rows_upserted' => 0,
            'last_error' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dump
     */
    private function markDumpProcessed(array $dump, int $rowsUpserted): void
    {
        $this->upsertDumpState($dump, [
            'status' => 'processed',
            'processed_at' => now()->format('Y-m-d H:i:s'),
            'rows_upserted' => $rowsUpserted,
            'last_error' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dump
     */
    private function markDumpFailed(array $dump, string $error): void
    {
        $this->upsertDumpState($dump, [
            'status' => 'failed',
            'processed_at' => null,
            'rows_upserted' => 0,
            'last_error' => Str::limit($error, 65000, ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $dump
     * @param  array{status: string, processed_at: string|null, rows_upserted: int, last_error: string|null}  $state
     */
    private function upsertDumpState(array $dump, array $state): void
    {
        $now = now()->format('Y-m-d H:i:s');
        $dumpId = (int) data_get($dump, 'id', 0);

        DB::table('atheletic_data_dumps')->upsert(
            [[
                'dump_type' => self::DUMP_TYPE,
                'dump_id' => $dumpId,
                'table_type' => data_get($dump, 'table_type'),
                'file_path' => data_get($dump, 'file_path'),
                'from_datetime' => $this->parseNullableDatetime(data_get($dump, 'from_datetime')),
                'to_datetime' => $this->parseNullableDatetime(data_get($dump, 'to_datetime')),
                'status' => $state['status'],
                'processed_at' => $state['processed_at'],
                'rows_upserted' => $state['rows_upserted'],
                'last_error' => $state['last_error'],
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['dump_type', 'dump_id'],
            [
                'table_type',
                'file_path',
                'from_datetime',
                'to_datetime',
                'status',
                'processed_at',
                'rows_upserted',
                'last_error',
                'updated_at',
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @param  array<int, string>  $payloadColumns
     */
    private function flushBatch(int $dumpId, array &$batch, int $rowsUpserted, array $payloadColumns): int
    {
        $batchCount = count($batch);
        $this->upsertBatch($batch, $payloadColumns);
        $batch = [];

        $rowsUpserted += $batchCount;
        $this->updateDumpProgress($dumpId, $rowsUpserted);

        return $rowsUpserted;
    }

    /**
     * @return array<int, bool>
     */
    private function processedDumpLookup(bool $reprocess): array
    {
        if ($reprocess) {
            return [];
        }

        return DB::table('atheletic_data_dumps')
            ->where('dump_type', self::DUMP_TYPE)
            ->whereNotNull('processed_at')
            ->pluck('dump_id')
            ->mapWithKeys(fn (mixed $id): array => [(int) $id => true])
            ->all();
    }

    private function updateDumpProgress(int $dumpId, int $rowsUpserted): void
    {
        DB::table('atheletic_data_dumps')
            ->where('dump_type', self::DUMP_TYPE)
            ->where('dump_id', $dumpId)
            ->update([
                'status' => 'processing',
                'rows_upserted' => $rowsUpserted,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);
    }
}