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
        Schema::create('ppc_package_pl_capability', function (Blueprint $table) {
            $table->id();
            $table->string('package', 45);
            $table->string('production_line', 10);
            $table->string('factory', 10)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['package', 'production_line', 'factory', 'valid_from'], 'uq_capability_range');
            $table->index('package', 'idx_capability_package');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppc_package_pl_capability');
    }
};
