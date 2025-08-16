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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            // الربط والفترة الزمنية
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            // القيم المالية
            $table->decimal('base_salary', 10, 2);
            $table->decimal('leave_deduction', 10, 2)->default(0);
            $table->decimal('penalty_deduction', 10, 2)->default(0);
            $table->decimal('final_salary', 10, 2);
            // معلومات إضافية
            $table->enum('status', ['unpaid', 'paid', 'processing'])->default('unpaid');

            $table->timestamps();

        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
