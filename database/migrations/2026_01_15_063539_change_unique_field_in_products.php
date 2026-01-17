<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // 1. Hapus Unique di part_number (jika ada)
            // Kita gunakan try-catch atau cek index agar aman, tapi cara standar array ['col'] biasanya mendeteksi nama index otomatis
            $table->dropUnique(['part_number']); 

            // 2. Tambahkan Unique di code_part
            $table->unique('code_part');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code_part']);
            $table->unique('part_number');
        });
    }
};