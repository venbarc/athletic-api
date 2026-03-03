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
        Schema::create('athelas_data_dumps', function (Blueprint $table) {
            $table->id();
            $table->string('dump_type', 32);
            $table->unsignedBigInteger('dump_id');
            $table->string('table_type', 64)->nullable();
            $table->string('file_path')->nullable();
            $table->dateTime('from_datetime')->nullable();
            $table->dateTime('to_datetime')->nullable();
            $table->string('status', 32)->default('pending');
            $table->dateTime('processed_at')->nullable();
            $table->unsignedBigInteger('rows_upserted')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['dump_type', 'dump_id']);
            $table->index(['dump_type', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athelas_data_dumps');
    }
};

