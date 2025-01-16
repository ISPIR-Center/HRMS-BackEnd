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
        Schema::create('ipcrs', function (Blueprint $table) {
            $table->id();

            $table->string('employee_no');
            $table->foreign('employee_no')->references('employee_no')->on('employees')->onDelete('cascade');

            $table->unsignedBigInteger('ipcr_period_id')->nullable();
            $table->foreign('ipcr_period_id')->references('id')->on('ipcr_periods')->onDelete('cascade');

            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->foreign('submitted_by')->references('id')->on('users');

            $table->unsignedBigInteger('validated_by')->nullable();
            $table->foreign('validated_by')->references('id')->on('users');
            
            $table->float('numerical_rating', 3, 2)->nullable(); 
            $table->string('adjectival_rating')->nullable();
            $table->date('submitted_date');
            $table->date('validated_date')->nullable();
            
            $table->string('file_path')->nullable(); 
            $table->string('status')->default('Pending');
            // $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipcrs');
    }
};



