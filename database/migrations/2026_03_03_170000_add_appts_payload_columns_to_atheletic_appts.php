<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = Schema::hasTable('atheletic_appts')
            ? 'atheletic_appts'
            : (Schema::hasTable('athelas_appts') ? 'athelas_appts' : null);

        if ($tableName === null) {
            return;
        }

        $columns = $this->columns();
        $missing = array_values(array_filter($columns, fn (string $column): bool => ! Schema::hasColumn($tableName, $column)));

        if ($missing === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($missing): void {
            foreach ($missing as $column) {
                $table->longText($column)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Schema::hasTable('atheletic_appts')
            ? 'atheletic_appts'
            : (Schema::hasTable('athelas_appts') ? 'athelas_appts' : null);

        if ($tableName === null) {
            return;
        }

        $existing = array_values(array_filter($this->columns(), fn (string $column): bool => Schema::hasColumn($tableName, $column)));

        if ($existing === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existing): void {
            $table->dropColumn($existing);
        });
    }

    /**
     * @return array<int, string>
     */
    private function columns(): array
    {
        return [
            'payload_id',
            'payload_created_at',
            'payload_updated_at',
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
    }
};