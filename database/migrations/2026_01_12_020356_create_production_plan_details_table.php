<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_plan_details', function (Blueprint $table) {
            $table->id();

            // Relasi ke Header Plan (PENTING: onDelete cascade)
            $table->foreignId('production_plan_id')->constrained('production_plans')->onDelete('cascade');

            $table->foreignId('product_id')->constrained('products');
            $table->integer('qty_plan');
            $table->integer('qty_actual')->default(0);

            // Kolom untuk menyimpan hasil hitungan rumus
            $table->decimal('calculated_loading_pct', 5, 2);
            $table->integer('calculated_manpower');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plan_details');
    }
};
