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
        Schema::table('bom_details', function (Blueprint $table) {
            // Letakkan setelah kolom 'quantity' sesuai struktur DB Anda
            $table->integer('sequence')->default(0)->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('bom_details', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });
    }
};
