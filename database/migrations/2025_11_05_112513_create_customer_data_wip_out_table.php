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
        Schema::create('customer_data_wip_out', function (Blueprint $table) {
            $table->id();


            $table->string('part_name', 100)->nullable();
            $table->string('lot_id', 50)->nullable();
            $table->dateTime('out_date')->nullable();
            $table->integer('qty')->nullable();
            $table->string('residual', 20)->nullable();
            $table->string('test_part', 100)->nullable();
            $table->string('test_lot_id', 50)->nullable();
            $table->string('focus_group', 20)->nullable();
            $table->string('package', 45)->nullable();
            $table->string('process_site', 30)->nullable();
            $table->string('test_site', 45)->nullable();
            $table->string('tray', 50)->nullable();
            $table->string('bulk', 20)->nullable();
            $table->dateTime('date_loaded')->nullable();
            $table->string('process_group', 30)->nullable();
            $table->string('ramp_time', 10)->nullable();
            $table->string('imported_by', 7)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_data_wip_out');
    }
};
