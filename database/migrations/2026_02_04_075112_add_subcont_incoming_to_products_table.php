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
            // Tambahkan 2 kolom baru setelah outgoing
            $table->integer('subcont')->default(0)->after('outgoing');
            $table->integer('incoming')->default(0)->after('subcont');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['subcont', 'incoming']);
        });
    }
};
