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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique(); // Index otomatis karena unique
            $table->string('part_name');
            $table->string('uom', 10)->default('PCS');

            // Data teknis untuk rumus TPS
            $table->decimal('cycle_time', 8, 2)->comment('Detik per unit');
            $table->integer('qty_per_box');
            $table->decimal('safety_stock', 10, 2); // Buffer stock

            $table->timestamps();
            $table->softDeletes(); // Agar data master aman
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
