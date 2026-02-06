<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_plans', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date'); // Tanggal Plan (YYYY-MM-DD)
            $table->string('code_part', 50)->index(); // Kode Part
            $table->integer('qty')->default(0); // Jumlah Plan dari Google Sheet
            $table->timestamps();
            
            // Index agar pencarian cepat saat loading matrix
            $table->index(['plan_date', 'code_part']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_plans');
    }
};