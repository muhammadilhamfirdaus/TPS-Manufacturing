<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bom_details', function (Blueprint $table) {
            $table->id();
            // Produk Induk (Yang akan dibuat)
            $table->foreignId('parent_product_id')->constrained('products')->onDelete('cascade');

            // Produk Anak (Komponen/Bahan baku)
            $table->foreignId('child_product_id')->constrained('products')->onDelete('cascade');

            // Jumlah pemakaian (Misal: butuh 2 pcs baut untuk 1 part)
            $table->decimal('quantity', 10, 4);

            $table->timestamps();

            // Mencegah duplikasi (Part A tidak boleh punya komponen Part B ganda)
            $table->unique(['parent_product_id', 'child_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_details');
    }
};
