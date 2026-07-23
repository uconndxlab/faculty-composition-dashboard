<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('research_activity_class')->nullable()->after('is_public');
        });

        Schema::table('faculty_summaries', function (Blueprint $table) {
            $table->string('research_activity_class')->nullable()->after('is_public');
        });

        Schema::table('faculty_trends', function (Blueprint $table) {
            $table->string('research_activity_class')->nullable()->after('is_public');
        });

        Schema::table('similarity_rankings', function (Blueprint $table) {
            $table->string('research_activity_class')->nullable()->after('is_public');
        });

        Schema::table('trajectory_similarities', function (Blueprint $table) {
            $table->string('research_activity_class')->nullable()->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('trajectory_similarities', function (Blueprint $table) {
            $table->dropColumn('research_activity_class');
        });

        Schema::table('similarity_rankings', function (Blueprint $table) {
            $table->dropColumn('research_activity_class');
        });

        Schema::table('faculty_trends', function (Blueprint $table) {
            $table->dropColumn('research_activity_class');
        });

        Schema::table('faculty_summaries', function (Blueprint $table) {
            $table->dropColumn('research_activity_class');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('research_activity_class');
        });
    }
};
