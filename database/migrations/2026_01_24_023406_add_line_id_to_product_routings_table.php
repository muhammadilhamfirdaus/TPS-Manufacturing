<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_routings', function (Blueprint $table) {
            // Tambahkan kolom production_line_id setelah product_id
            // Kita buat nullable dulu agar data lama tidak error, tapi nanti wajib diisi lewat form
            $table->foreignId('production_line_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('production_lines')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('product_routings', function (Blueprint $table) {
            $table->dropForeign(['production_line_id']);
            $table->dropColumn('production_line_id');
        });
    }
};