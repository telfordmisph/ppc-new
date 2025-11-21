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
        Schema::create('f3_raw_packages', function (Blueprint $table) {
            $table->id();
            $table->string('raw_package', 100);
            $table->string('lead_count', 50)->nullable();
            $table->unsignedBigInteger('package_id'); // references f3_package_names
            $table->string('dimension', 50)->nullable();

            // Foreign key constraint
            $table->foreign('package_id')
                ->references('id')
                ->on('f3_package_names')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // No timestamps, since your table doesn’t have created_at/updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f3_raw_packages');
    }
};
