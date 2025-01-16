<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->string('employee_no')->primary();

            $table->unsignedBigInteger('employment_type_id')->nullable();
            $table->foreign('employment_type_id')->references('id')->on('employment_types')->onDelete('cascade');

            $table->unsignedBigInteger('classification_id')->nullable();
            $table->foreign('classification_id')->references('id')->on('employee_classifications')->onDelete('cascade');

            $table->unsignedBigInteger('office_id')->nullable();
            $table->foreign('office_id')->references('id')->on('offices')->onDelete('cascade');

            $table->string('suffix')->nullable();            
            $table->string('first_name');                    
            $table->string('middle_name')->nullable();       
            $table->string('last_name');                     
            $table->string('email_address')->unique()->nullable();       
            $table->string('mobile_no')->nullable();         
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('google_scholar_link')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};


