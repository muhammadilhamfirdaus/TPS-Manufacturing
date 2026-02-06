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
        Schema::table('production_plans', function (Blueprint $table) {
            // Ubah menjadi string/varchar agar fleksibel menerima 'HISTORY'
            $table->string('status', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            //
        });
    }
};
