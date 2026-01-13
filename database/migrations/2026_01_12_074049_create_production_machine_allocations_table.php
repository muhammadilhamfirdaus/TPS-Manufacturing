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
        Schema::create('production_machine_allocations', function (Blueprint $table) {
            $table->id();
            // Relasi ke Detail Plan (Produk apa, Plan tanggal berapa)
            $table->foreignId('production_plan_detail_id')
                ->constrained('production_plan_details')
                ->onDelete('cascade')
                ->name('fk_alloc_plan_detail'); // Nama constraint dipendekkan biar gak error

            // Relasi ke Mesin (Dikerjakan di mesin mana)
            $table->foreignId('machine_id')
                ->constrained('machines')
                ->onDelete('cascade');

            $table->integer('allocated_qty')->default(0); // Input User (Pcs)
            $table->decimal('calculated_hours', 10, 2)->default(0); // Hasil Hitung (Jam)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_machine_allocations');
    }
};
