<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('unitid')->nullable()->index();
            $table->string('name')->nullable();
            $table->integer('top_public_rank_nat_univ')->nullable()->index();
            // Enrollment and scale
            $table->integer('total_enrollment')->nullable();
            $table->integer('total_faculty')->nullable();
            $table->decimal('student_faculty_ratio', 10, 4)->nullable();
            // Outcomes
            $table->decimal('firstyr_retention_rate', 10, 4)->nullable();
            $table->decimal('grad_rate_6yr_cohort', 10, 4)->nullable();
            $table->decimal('grad_rate_6yr_pell', 10, 4)->nullable();
            $table->decimal('pell_pct_cohort', 10, 4)->nullable();
            $table->decimal('acceptance_rate', 10, 4)->nullable();
            // Faculty and finances
            $table->integer('avg_faculty_salary')->nullable();
            $table->integer('edu_exp_per_student')->nullable();
            $table->bigInteger('total_rd_expenditures')->nullable();
            $table->integer('inst_tuition_fees')->nullable();
            $table->integer('net_cost')->nullable();
            // Institutional characteristics
            $table->string('medical_degrees')->nullable();
            $table->string('degree_of_urbanization')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_rankings');
    }
};
