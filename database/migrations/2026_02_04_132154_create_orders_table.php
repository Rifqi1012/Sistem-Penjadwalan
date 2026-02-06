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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->date('order_date');            // tanggal ditampung
            $table->date('start_date');            // default = order_date + 1
            $table->unsignedInteger('bales')->default(0);
            $table->unsignedInteger('pcs_total');  // bales*800 atau input pcs
            $table->unsignedInteger('pcs_remaining');
            $table->enum('status', ['queued', 'scheduled', 'in_progress', 'done'])->default('queued');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
