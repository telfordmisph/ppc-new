<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE f3 ADD COLUMN production_line VARCHAR(10) DEFAULT NULL, ALGORITHM=INSTANT');
        DB::statement('ALTER TABLE f3 ADD INDEX idx_wip_production_line (production_line), ALGORITHM=INPLACE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('f3', function (Blueprint $table) {
            //
        });
    }
};
