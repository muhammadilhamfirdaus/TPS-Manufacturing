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
        Schema::table('product_routings', function (Blueprint $table) { // Ubah nama tabel di sini
            $table->float('manpower_ratio')->default(1)->after('pcs_per_hour');
        });
    }

    public function down()
    {
        Schema::table('product_routings', function (Blueprint $table) { // Ubah di sini juga
            $table->dropColumn('manpower_ratio');
        });
    }
};
