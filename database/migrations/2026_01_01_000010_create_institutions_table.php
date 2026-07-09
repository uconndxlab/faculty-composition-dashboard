<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('unitid')->nullable();
            $table->string('name');
            $table->string('state')->nullable();
            $table->string('sector')->nullable();
            $table->string('carnegie_classification')->nullable();
            $table->boolean('is_uconn')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
