<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->default(1)->constrained()->onDelete('cascade');
            $table->string('certificate')->nullable();
            $table->string('medical_license_number')->nullable();
            $table->string('image')->nullable();
            $table->string('specialist')->default('undefined');
            $table->unsignedBigInteger('number_of_treatments')->default(0);
            $table->string('bio')->default('');
            $table->unsignedBigInteger('years_of_experience')->nullable();
            //rating
            $table->float('initial_rating')->default(3);
            $table->float('final_rating')->default(3);
            $table->unsignedInteger('rating_votes')->default(0);
            $table->unsignedInteger('rating_total')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
