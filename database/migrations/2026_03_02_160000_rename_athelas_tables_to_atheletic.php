<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('athelas_data_dumps') && ! Schema::hasTable('atheletic_data_dumps')) {
            Schema::rename('athelas_data_dumps', 'atheletic_data_dumps');
        }

        if (Schema::hasTable('athelas_appts') && ! Schema::hasTable('atheletic_appts')) {
            Schema::rename('athelas_appts', 'atheletic_appts');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('atheletic_data_dumps') && ! Schema::hasTable('athelas_data_dumps')) {
            Schema::rename('atheletic_data_dumps', 'athelas_data_dumps');
        }

        if (Schema::hasTable('atheletic_appts') && ! Schema::hasTable('athelas_appts')) {
            Schema::rename('atheletic_appts', 'athelas_appts');
        }
    }
};

