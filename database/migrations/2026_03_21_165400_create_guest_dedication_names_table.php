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
        Schema::create('guest_dedication_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->string('name', 255);
            $table->timestamps();

            $table->unique(['guest_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_dedication_names');
    }
};
