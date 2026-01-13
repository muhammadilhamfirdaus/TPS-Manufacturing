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
        Schema::table('machines', function (Blueprint $table) {
            // Ganti nama kolom biar lebih umum (bisa diisi TON, bisa diisi NAMA GROUP lain)
            $table->renameColumn('tonnage', 'machine_group');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->renameColumn('machine_group', 'tonnage');
        });
    }
};
