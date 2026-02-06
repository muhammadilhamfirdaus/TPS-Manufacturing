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
        Schema::table('products', function (Blueprint $table) {
            // Kolom yang mungkin belum ada
            $table->integer('lead_time')->default(1)->comment('Dalam Hari');
            // $table->integer('safety_stock')->default(0); // Sudah ada di kode sebelumnya
            // $table->integer('qty_per_box')->default(1);  // Sudah ada di kode sebelumnya
            $table->string('kanban_type')->default('SUPPLIER')->comment('SUPPLIER/INTERNAL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
