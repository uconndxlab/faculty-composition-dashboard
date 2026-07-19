<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecasting_outputs', function (Blueprint $table) {
            $table->id();
            // Scenario parameters — indexed for fast filtering
            $table->decimal('ntt_per_tt_loss', 18, 9)->nullable()->index();
            $table->decimal('student_growth_rate', 18, 9)->nullable()->index();
            $table->decimal('replacement_rate', 18, 9)->nullable()->index();
            // Composite index so the three-parameter WHERE clause hits one index
            $table->index(['ntt_per_tt_loss', 'student_growth_rate', 'replacement_rate'], 'forecasting_scenario_idx');
            $table->integer('year')->nullable()->index();
            // Precomputed model outputs
            $table->decimal('assistant', 18, 9)->nullable();
            $table->decimal('associate', 18, 9)->nullable();
            $table->decimal('full', 18, 9)->nullable();
            $table->decimal('ntt', 18, 9)->nullable();
            $table->decimal('total_faculty', 18, 9)->nullable();
            $table->decimal('total_students', 18, 9)->nullable();
            $table->decimal('student_ntt_ratio', 18, 9)->nullable();
            $table->decimal('student_faculty_ratio', 18, 9)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecasting_outputs');
    }
};
