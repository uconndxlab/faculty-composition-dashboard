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
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trajectory_similarities');
    }
};
