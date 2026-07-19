<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('similarity_rankings', function (Blueprint $table) {
            $table->id();
            // Identity
            $table->string('unitid')->nullable()->index();
            $table->string('institution')->nullable()->index();
            $table->string('sector')->nullable();
            $table->string('carnegie_classification')->nullable();
            $table->string('medical_degree_flag')->nullable();
            $table->string('hospital_flag')->nullable();
            $table->integer('year')->nullable()->index();
            $table->integer('total_faculty')->nullable();
            // Cross-tab percentage columns (tenure-status × rank)
            $table->decimal('pct_tenured_professor', 18, 9)->nullable();
            $table->decimal('pct_tenured_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_tenured_assistant_professor', 18, 9)->nullable();
            $table->decimal('pct_t_track_professor', 18, 9)->nullable();
            $table->decimal('pct_t_track_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_t_track_assistant_professor', 18, 9)->nullable();
            $table->decimal('pct_non_tenure_professor', 18, 9)->nullable();
            $table->decimal('pct_non_tenure_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_non_tenure_assistant_professor', 18, 9)->nullable();
            // Distance columns
            $table->decimal('uconn_euclidean_distance', 18, 9)->nullable();
            $table->decimal('rank_euclidean_distance', 18, 9)->nullable();
            $table->decimal('tenure_euclidean_distance', 18, 9)->nullable();
            $table->decimal('detailed_cell_euclidean_distance', 18, 9)->nullable();
            // Rank mix percentages
            $table->decimal('pct_professor', 18, 9)->nullable();
            $table->decimal('pct_associate_professor', 18, 9)->nullable();
            $table->decimal('pct_assistant_professor', 18, 9)->nullable();
            // Tenure mix percentages
            $table->decimal('pct_tenured', 18, 9)->nullable();
            $table->decimal('pct_t_track', 18, 9)->nullable();
            $table->decimal('pct_non_tenure', 18, 9)->nullable();
            // Tenure-system and ranked-rank percentages
            $table->decimal('pct_tenure_system', 18, 9)->nullable();
            $table->decimal('pct_professor_ranked', 18, 9)->nullable();
            $table->decimal('pct_associate_professor_ranked', 18, 9)->nullable();
            $table->decimal('pct_assistant_professor_ranked', 18, 9)->nullable();
            // Percent-rank columns
            $table->decimal('nine_d_rank_pct', 18, 9)->nullable();
            $table->decimal('rank_rank_pct', 18, 9)->nullable();
            $table->decimal('tenure_rank_pct', 18, 9)->nullable();
            // Composite score
            $table->decimal('composite_similarity_score', 18, 9)->nullable();
            // Rank columns
            $table->integer('rank_similarity_rank')->nullable()->index();
            $table->integer('tenure_similarity_rank')->nullable()->index();
            $table->integer('composite_similarity_rank')->nullable()->index();
            $table->integer('euclidean_similarity_rank')->nullable();
            $table->integer('nine_d_similarity_rank')->nullable()->index();
            $table->integer('detailed_cell_similarity_rank')->nullable()->index();
            $table->integer('detailed_cell_distance_pct_rank')->nullable();
            $table->integer('rank_distance_pct_rank')->nullable();
            $table->integer('tenure_distance_pct_rank')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('similarity_rankings');
    }
};
