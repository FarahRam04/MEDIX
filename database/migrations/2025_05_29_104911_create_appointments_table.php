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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');

            $table->date('date'); // تاريخ الحجز
            $table->foreignId('slot_id')->constrained('available_slots')->onDelete('cascade'); // وقت الحجز الثابت

            $table->enum('type', ['check_up', 'follow_up']);
            $table->string('specialization');
            $table->enum('status', ['pending', 'completed'])->default('pending');

            $table->unsignedBigInteger('check_up_price')->default(50000);
            $table->boolean('lab_tests')->default(false);
            $table->unsignedBigInteger('total_price')->nullable();
            $table->boolean('payment_status')->default(false);

            $table->boolean('with_medical_report')->default(false);
            $table->boolean('is_prescription_viewed')->default(false);
            $table->boolean('is_rated')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
