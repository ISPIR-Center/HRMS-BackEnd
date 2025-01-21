<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipcr_periods', function (Blueprint $table) {
            $table->id();
            $table->date('start_month_year');
            $table->date('end_month_year');
            $table->string('ipcr_period_type');
            $table->string('ipcr_type');
            $table->boolean('active_flag')->default(false);
            $table->timestamps();

            $table->index('active_flag');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipcr_periods');
    }
};
