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
        Schema::table('production_plan_details', function (Blueprint $table) {
            // Menambahkan kolom loading_hours setelah qty_plan
            $table->double('loading_hours', 15, 2)->default(0)->after('qty_plan');
        });
    }

    public function down()
    {
        Schema::table('production_plan_details', function (Blueprint $table) {
            $table->dropColumn('loading_hours');
        });
    }
};
