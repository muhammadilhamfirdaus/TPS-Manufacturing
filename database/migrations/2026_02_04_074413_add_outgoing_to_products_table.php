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
            // Menambahkan kolom outgoing, default 0, diletakkan setelah kolom conveyance (agar rapi di DB)
            // Sesuaikan 'after' jika ingin urutan DB beda, tapi tidak pengaruh ke tampilan.
            $table->integer('outgoing')->default(0)->after('conveyance');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('outgoing');
        });
    }
};
