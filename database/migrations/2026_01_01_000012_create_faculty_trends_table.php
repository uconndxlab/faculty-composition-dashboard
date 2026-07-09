<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_trends', function (Blueprint $table) {
            $table->id();
            $table->string('unitid')->nullable()->index();
            $table->string('institution')->nullable()->index();
            $table->string('sector')->nullable();
            $table->string('carnegie_classification')->nullable();
            $table->string('medical_degree_flag')->nullable();
            $table->string('hospital_flag')->nullable();
            $table->string('metric')->nullable()->index();
            $table->integer('first_year')->nullable();
            $table->integer('last_year')->nullable();
            $table->integer('n_years')->nullable();
            $table->decimal('first_value', 18, 9)->nullable();
            $table->decimal('last_value', 18, 9)->nullable();
            $table->decimal('absolute_change', 18, 9)->nullable();
            $table->decimal('percent_change', 18, 9)->nullable();
            $table->decimal('average_annual_change', 18, 9)->nullable();
            $table->decimal('slope', 18, 9)->nullable();
            $table->decimal('intercept', 18, 9)->nullable();
            $table->decimal('r_squared', 18, 9)->nullable();
            $table->decimal('p_value', 18, 9)->nullable();
            $table->decimal('std_error', 18, 9)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_trends');
    }
};
