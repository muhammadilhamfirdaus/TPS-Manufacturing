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
    Schema::create('production_plans', function (Blueprint $table) {
        $table->id();
        
        // KOLOM INI YANG HILANG:
        $table->date('plan_date'); 
        $table->foreignId('production_line_id')->constrained('production_lines');
        $table->integer('shift_id')->default(1); // Kita buat simpel integer dulu
        $table->enum('status', ['DRAFT', 'APPROVED', 'CLOSED'])->default('DRAFT');
        $table->foreignId('created_by')->nullable()->constrained('users');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};
