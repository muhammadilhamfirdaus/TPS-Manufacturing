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
        Schema::table('machines', function (Blueprint $table) {
            // Default INTERNAL agar data lama aman
            $table->string('type')->default('INTERNAL')->after('name'); // Pilihan: INTERNAL, SUBCONT
        });
    }

    public function down()
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
