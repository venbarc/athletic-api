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
        Schema::create('atheletic_eligibility_checks', function (Blueprint $table) {
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
            $table->longText('athelas_task_id')->nullable();
            $table->longText('cmds_task_id')->nullable();
            $table->longText('raw_request')->nullable();
            $table->longText('raw_response_url')->nullable();
            $table->longText('check_source')->nullable();
            $table->longText('has_error')->nullable();
            $table->longText('created_by')->nullable();
            $table->longText('date_of_service')->nullable();
            $table->longText('parsed_data_url')->nullable();
            $table->longText('parsed_data')->nullable();
            $table->longText('oon_parsed_data')->nullable();
            $table->longText('suggested_copay_amt')->nullable();
            $table->longText('is_deductible_met')->nullable();
            $table->longText('is_in_network')->nullable();
            $table->longText('plan_info')->nullable();
            $table->longText('service_type_codes')->nullable();
            $table->longText('is_proactive_visit')->nullable();
            $table->longText('is_archived')->nullable();
            $table->longText('failed_to_parse_response_reason')->nullable();
            $table->longText('payer_plan_info')->nullable();
            $table->longText('medicare_part')->nullable();
            $table->longText('waystar_inquiry_id')->nullable();
            $table->longText('created_by_user_id')->nullable();
            $table->longText('appointment_id')->nullable();
            $table->longText('patient_insurance_info_id')->nullable();
            $table->longText('site_id')->nullable();
            $table->longText('facility_id')->nullable();
            $table->longText('eligibility_coverage_id')->nullable();
            $table->longText('mapped_insurance_company_id')->nullable();
            $table->longText('patient_id')->nullable();
            $table->longText('eligibility_checks_updated_at')->nullable();
            $table->longText('patients_updated_at')->nullable();

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
        Schema::dropIfExists('atheletic_eligibility_checks');
    }
};