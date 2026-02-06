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
        // Cek dulu apakah kolom SUDAH ADA?
        if (!Schema::hasColumn('products', 'kanban_aktif')) {
            Schema::table('products', function (Blueprint $table) {
                // Kode asli Anda ada di sini
                $table->integer('kanban_aktif')->default(0)->after('fluctuation');
            });
        }
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('kanban_aktif');
        });
    }
};
