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
        Schema::create('work_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');                   // tanggal produksi (mis: besok)
            $table->unsignedSmallInteger('pcs');         // <= 240
            $table->enum('status', ['planned', 'done'])->default('planned');
            $table->timestamps();

            $table->index(['work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_chunks');
    }
};
