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
            $table->integer('lot_size_pcs')->default(0)->after('qty_per_box');
            $table->string('material_remarks')->nullable()->after('lot_size_pcs'); // Untuk kolom Iris/Btg
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['lot_size_pcs', 'material_remarks']);
        });
    }
};
