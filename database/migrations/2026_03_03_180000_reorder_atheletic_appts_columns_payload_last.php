<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = $this->resolveTable();

        if ($table === null || ! $this->supportsColumnReorder()) {
            return;
        }

        $existing = Schema::getColumnListing($table);

        $ordered = [
            'id',
            'source_id',
            'dump_id',
            'from_datetime',
            'to_datetime',
            'source_updated_at',
            'appointment_status',
            'created_at',
            'updated_at',
            'payload_created_at',
            'payload_updated_at',
            'payload_id',
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
            'prior_auth_id_secondary',
            'prior_auth_id_tertiary',
            'ehr_appointment_types_updated_at',
            'appointments_auxiliary_data_updated_at',
            'appointments_updated_at',
        ];

        $leftovers = array_values(array_diff($existing, array_merge($ordered, ['payload'])));
        $target = array_values(array_filter(
            array_merge($ordered, $leftovers, ['payload']),
            fn (string $column): bool => in_array($column, $existing, true)
        ));

        $definitions = $this->columnDefinitions($table);
        $previous = null;

        foreach ($target as $column) {
            if (! isset($definitions[$column])) {
                continue;
            }

            $sql = "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definitions[$column]}";

            if ($previous !== null) {
                $sql .= " AFTER `{$previous}`";
            }

            DB::statement($sql);
            $previous = $column;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: this migration only normalizes column display order.
    }

    private function resolveTable(): ?string
    {
        if (Schema::hasTable('atheletic_appts')) {
            return 'atheletic_appts';
        }

        if (Schema::hasTable('athelas_appts')) {
            return 'athelas_appts';
        }

        return null;
    }

    private function supportsColumnReorder(): bool
    {
        $driver = DB::getDriverName();

        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    /**
     * @return array<string, string>
     */
    private function columnDefinitions(string $table): array
    {
        $definitions = [];
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");

        foreach ($columns as $column) {
            $field = (string) $column->Field;
            $type = (string) $column->Type;
            $nullable = ((string) $column->Null) === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $column->Default;
            $extra = trim((string) $column->Extra);

            $sql = "{$type} {$nullable}";

            $isTextOrBlob = (bool) preg_match('/(text|blob)/i', $type);
            $isAutoIncrement = str_contains(strtolower($extra), 'auto_increment');

            if (! $isAutoIncrement && ! $isTextOrBlob) {
                if ($default !== null) {
                    $defaultValue = strtoupper((string) $default) === 'CURRENT_TIMESTAMP'
                        ? 'CURRENT_TIMESTAMP'
                        : "'".str_replace("'", "''", (string) $default)."'";
                    $sql .= " DEFAULT {$defaultValue}";
                } elseif (((string) $column->Null) === 'YES') {
                    $sql .= ' DEFAULT NULL';
                }
            }

            if ($extra !== '') {
                $sql .= " {$extra}";
            }

            $definitions[$field] = trim($sql);
        }

        return $definitions;
    }
};