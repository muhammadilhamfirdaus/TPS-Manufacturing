<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_routings', function (Blueprint $table) {
            // CT per proses (Mesin A = 5 detik, Mesin B = 10 detik)
            $table->decimal('cycle_time', 8, 2)->default(0)->after('process_name');
        });
    }

    public function down(): void
    {
        Schema::table('product_routings', function (Blueprint $table) {
            $table->dropColumn('cycle_time');
        });
    }
};
