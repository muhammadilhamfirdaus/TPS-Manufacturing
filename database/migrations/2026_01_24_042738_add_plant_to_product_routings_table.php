<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_routings', function (Blueprint $table) {
            // Menambahkan kolom 'plant' (boleh kosong / nullable)
            $table->string('plant', 50)->nullable()->after('process_name');
        });
    }

    public function down(): void
    {
        Schema::table('product_routings', function (Blueprint $table) {
            $table->dropColumn('plant');
        });
    }
};