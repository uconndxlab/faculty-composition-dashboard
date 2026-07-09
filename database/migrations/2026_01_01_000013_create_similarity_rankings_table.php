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
            $table->string('institution')->nullable()->index();
            $table->string('sector')->nullable();
            $table->string('carnegie_classification')->nullable();
            $table->string('hospital_flag')->nullable();
            $table->integer('total_faculty')->nullable();
            $table->integer('nine_d_similarity_rank')->nullable()->index();
            $table->integer('rank_similarity_rank')->nullable();
            $table->integer('tenure_similarity_rank')->nullable();
            $table->integer('composite_similarity_rank')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('similarity_rankings');
    }
};
