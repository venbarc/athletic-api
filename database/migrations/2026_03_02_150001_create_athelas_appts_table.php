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
        Schema::create('athelas_appts', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 191);
            $table->unsignedBigInteger('dump_id')->nullable();
            $table->dateTime('from_datetime')->nullable();
            $table->dateTime('to_datetime')->nullable();
            $table->dateTime('source_updated_at')->nullable();
            $table->string('appointment_status', 191)->nullable();
            $table->longText('payload');
            $table->timestamps();

            $table->unique('source_id');
            $table->index('dump_id');
            $table->index('source_updated_at');
            $table->index('appointment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athelas_appts');
    }
};

