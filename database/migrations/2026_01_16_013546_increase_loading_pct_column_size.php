<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('production_plan_details', function (Blueprint $table) {
            // Ubah dari (5,2) jadi (10,2) agar muat angka jutaan persen
            $table->decimal('calculated_loading_pct', 10, 2)->change();
        });
    }

    public function down()
    {
        Schema::table('production_plan_details', function (Blueprint $table) {
            // Kembalikan ke asal (sesuaikan dengan migrasi awal Anda, biasanya 5,2)
            $table->decimal('calculated_loading_pct', 5, 2)->change();
        });
    }
};
