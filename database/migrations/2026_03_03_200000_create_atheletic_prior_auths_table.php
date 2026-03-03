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
        Schema::create('atheletic_prior_auths', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 191);
            $table->unsignedBigInteger('dump_id')->nullable();
            $table->dateTime('from_datetime')->nullable();
            $table->dateTime('to_datetime')->nullable();
            $table->dateTime('source_updated_at')->nullable();

            // Internal sync timestamps
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Payload columns in requested order
            $table->longText('payload_created_at')->nullable();
            $table->longText('payload_updated_at')->nullable();
            $table->longText('payload_id')->nullable();
            $table->longText('prior_authorization_status')->nullable();
            $table->longText('patient_id')->nullable();
            $table->longText('appointment_id')->nullable();
            $table->longText('insurance_company_id')->nullable();
            $table->longText('insurance_priority')->nullable();
            $table->longText('prior_authorization_number')->nullable();
            $table->longText('mode_of_request')->nullable();
            $table->longText('reason')->nullable();
            $table->longText('notes')->nullable();
            $table->longText('prior_authorization_result')->nullable();
            $table->longText('expiration_date')->nullable();
            $table->longText('effective_date')->nullable();
            $table->longText('prior_auth_requested_id')->nullable();
            $table->longText('icd_10_codes')->nullable();
            $table->longText('cpt_codes')->nullable();
            $table->longText('visits_total')->nullable();
            $table->longText('visits_remaining')->nullable();
            $table->longText('prior_auth_type')->nullable();
            $table->longText('medical_records_attachment_url')->nullable();
            $table->longText('urgency')->nullable();
            $table->longText('encounter_id')->nullable();
            $table->longText('patient_insurance_info_id')->nullable();
            $table->longText('is_archived')->nullable();
            $table->longText('total_billable_units')->nullable();
            $table->longText('remaining_billable_units')->nullable();
            $table->longText('prior_auth_category')->nullable();
            $table->longText('referring_provider_id')->nullable();
            $table->longText('encounter_visits_total')->nullable();
            $table->longText('encounter_visits_remaining')->nullable();
            $table->longText('referring_facility_id')->nullable();
            $table->longText('prior_auth_tracking_type')->nullable();

            // Raw payload always kept for traceability and last-column convention
            $table->longText('payload');

            $table->unique('source_id');
            $table->index('dump_id');
            $table->index('source_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atheletic_prior_auths');
    }
};