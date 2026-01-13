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
        Schema::create('product_routings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('process_name')->nullable(); // Contoh: PIERCE BLANK, STAMPING
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_routings');
    }
};
