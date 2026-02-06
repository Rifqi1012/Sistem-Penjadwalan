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
        Schema::create('unit_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_chunk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1); // urutan di unit (hari itu)
            $table->timestamps();

            $table->unique(['work_chunk_id']); // 1 chunk ke 1 unit
            $table->index(['unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_assignments');
    }
};
