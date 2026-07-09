<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_summaries', function (Blueprint $table) {
            $table->id();
            // Identity columns preserved from source CSV
            $table->string('unitid')->nullable()->index();
            $table->string('institution')->nullable()->index();
            $table->string('sector')->nullable();
            $table->string('carnegie_classification')->nullable();
            $table->string('medical_degree_flag')->nullable();
            $table->string('hospital_flag')->nullable();
            $table->integer('year')->nullable()->index();
            // Faculty counts
            $table->integer('total_faculty')->nullable();
            $table->integer('tenured_total')->nullable();
            $table->integer('t_track_total')->nullable();
            $table->integer('non_tenure_total')->nullable();
            $table->integer('assistant_professor_total')->nullable();
            $table->integer('associate_professor_total')->nullable();
            $table->integer('professor_total')->nullable();
            $table->integer('non_tenure_assistant_professor')->nullable();
            $table->integer('non_tenure_associate_professor')->nullable();
            $table->integer('non_tenure_professor')->nullable();
            $table->integer('t_track_assistant_professor')->nullable();
            $table->integer('t_track_associate_professor')->nullable();
            $table->integer('t_track_professor')->nullable();
            $table->integer('tenured_assistant_professor')->nullable();
            $table->integer('tenured_associate_professor')->nullable();
            $table->integer('tenured_professor')->nullable();
            $table->integer('tenure_system_total')->nullable();
            $table->integer('senior_faculty_total')->nullable();
            $table->integer('check_tenure_total')->nullable();
            $table->integer('check_rank_total')->nullable();
            $table->integer('tenure_total_diff')->nullable();
            $table->integer('rank_total_diff')->nullable();
            // Percentages and ratios
            $table->decimal('pct_tenured', 18, 9)->nullable();
            $table->decimal('pct_t_track', 18, 9)->nullable();
            $table->decimal('pct_non_tenure', 18, 9)->nullable();
            $table->decimal('pct_professor', 18, 9)->nullable();
            $table->decimal('pct_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_assistant_professor', 18, 9)->nullable();
            $table->decimal('pct_tenured_professor', 18, 9)->nullable();
            $table->decimal('pct_tenured_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_tenured_assistant_professor', 18, 9)->nullable();
            $table->decimal('pct_t_track_professor', 18, 9)->nullable();
            $table->decimal('pct_t_track_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_t_track_assistant_professor', 18, 9)->nullable();
            $table->decimal('pct_non_tenure_professor', 18, 9)->nullable();
            $table->decimal('pct_non_tenure_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_non_tenure_assistant_professor', 18, 9)->nullable();
            $table->decimal('pct_tenure_system', 18, 9)->nullable();
            $table->decimal('non_tenure_to_tenure_system_ratio', 18, 9)->nullable();
            $table->decimal('pct_senior_faculty', 18, 9)->nullable();
            $table->decimal('assistant_to_senior_ratio', 18, 9)->nullable();
            $table->decimal('pct_tenure_sum', 18, 9)->nullable();
            $table->decimal('pct_rank_sum', 18, 9)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_summaries');
    }
};
