<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- PERBAIKAN DI SINI ---
        // Hapus tabel lama jika ada (agar tidak error "Table already exists")
        Schema::dropIfExists('production_actuals'); 
        // -------------------------

        Schema::create('production_actuals', function (Blueprint $table) {
            $table->id();
            $table->date('production_date'); // Tanggal Produksi
            $table->string('code_part', 50)->index(); // Kode Part
            
            // Kita siapkan kolom lengkap, tapi yang dipakai nanti qty_final
            $table->integer('qty_scan')->default(0); // Data Scan (Opsional)
            $table->integer('qty_delv')->default(0); // Data Manual (Utama)
            $table->integer('qty_final')->default(0); // Angka Final untuk Report
            
            $table->unsignedBigInteger('created_by')->nullable(); // Siapa yang input
            $table->timestamps();

            // Index biar cepat saat filter report
            $table->index(['production_date', 'code_part']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_actuals');
    }
};