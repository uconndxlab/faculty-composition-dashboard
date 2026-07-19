<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trajectory_similarities', function (Blueprint $table) {
            $table->id();
            $table->string('unitid')->nullable()->index();
            $table->string('institution')->nullable()->index();
            $table->string('sector')->nullable();
            $table->string('carnegie_classification')->nullable();
            $table->string('medical_degree_flag')->nullable();
            $table->string('hospital_flag')->nullable();
            $table->decimal('trajectory_distance_from_uconn', 18, 9)->nullable();
            $table->integer('trajectory_similarity_rank')->nullable()->index();
            $table->integer('n_shared_trajectory_metrics')->nullable();
            $table->decimal('slope_pct_non_tenure', 18, 9)->nullable();
            $table->decimal('slope_pct_t_track', 18, 9)->nullable();
            $table->decimal('slope_pct_tenure_system', 18, 9)->nullable();
            $table->decimal('slope_pct_tenured', 18, 9)->nullable();
            $table->decimal('pct_change_total_faculty', 18, 9)->nullable();
            // Additional slope columns
            $table->decimal('slope_pct_assistant_professor', 18, 9)->nullable();
            $table->decimal('slope_pct_associate_professor', 18, 9)->nullable();
            $table->decimal('slope_pct_professor', 18, 9)->nullable();
            $table->decimal('slope_pct_senior_faculty', 18, 9)->nullable();
            $table->decimal('slope_ugrds_per_faculty', 18, 9)->nullable();
            // Latest-year totals
            $table->integer('tenure_system_total')->nullable();
            $table->integer('non_tenure_total')->nullable();
            $table->integer('senior_faculty_total')->nullable();
            $table->decimal('ugrd_fte', 18, 9)->nullable();
            $table->decimal('grad_fte', 18, 9)->nullable();
            // Period-change deltas
            $table->decimal('tenure_system_total_delta', 18, 9)->nullable();
            $table->decimal('non_tenure_total_delta', 18, 9)->nullable();
            $table->decimal('senior_faculty_total_delta', 18, 9)->nullable();
            $table->decimal('ugrd_fte_delta', 18, 9)->nullable();
            $table->decimal('grad_fte_delta', 18, 9)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trajectory_similarities');
    }
};
