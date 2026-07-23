<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->boolean('is_aau_public')->default(false)->after('is_uconn');
            $table->string('public_private')->nullable()->after('sector');
            $table->boolean('is_public')->nullable()->after('public_private');
        });

        Schema::table('faculty_summaries', function (Blueprint $table) {
            $table->string('public_private')->nullable()->after('sector');
            $table->boolean('is_public')->nullable()->after('public_private');
        });

        Schema::table('faculty_trends', function (Blueprint $table) {
            $table->string('public_private')->nullable()->after('sector');
            $table->boolean('is_public')->nullable()->after('public_private');
        });

        Schema::table('similarity_rankings', function (Blueprint $table) {
            $table->string('public_private')->nullable()->after('sector');
            $table->boolean('is_public')->nullable()->after('public_private');
        });

        Schema::table('trajectory_similarities', function (Blueprint $table) {
            $table->string('public_private')->nullable()->after('sector');
            $table->boolean('is_public')->nullable()->after('public_private');
        });
    }

    public function down(): void
    {
        Schema::table('trajectory_similarities', function (Blueprint $table) {
            $table->dropColumn(['public_private', 'is_public']);
        });

        Schema::table('similarity_rankings', function (Blueprint $table) {
            $table->dropColumn(['public_private', 'is_public']);
        });

        Schema::table('faculty_trends', function (Blueprint $table) {
            $table->dropColumn(['public_private', 'is_public']);
        });

        Schema::table('faculty_summaries', function (Blueprint $table) {
            $table->dropColumn(['public_private', 'is_public']);
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['is_aau_public', 'public_private', 'is_public']);
        });
    }
};
