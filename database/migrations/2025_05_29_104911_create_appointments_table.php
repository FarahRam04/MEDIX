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
            $table->foreignId('offer_id')->nullable()->constrained('offers')->onDelete('set null');


            $table->date('date'); // تاريخ الحجز
            $table->foreignId('slot_id')->constrained('available_slots')->onDelete('cascade'); // وقت الحجز الثابت

            $table->string('specialization');
            $table->json('status');

            $table->enum('type', ['check_up', 'follow_up']);
            $table->boolean('with_medical_report')->default(false);
            $table->string('medical_report_path')->nullable();
            $table->unsignedBigInteger('init_total_price')->nullable();
            $table->unsignedBigInteger('final_total_price')->nullable();
            $table->boolean('payment_status')->default(false);
            $table->date('payment_date')->nullable();
            $table->time('payment_time')->nullable();




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
