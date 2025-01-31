<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('employment_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_types');
    }
};
