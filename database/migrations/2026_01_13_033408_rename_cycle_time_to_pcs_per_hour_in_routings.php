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
            // Ganti nama kolom dan tipe datanya (Integer lebih cocok untuk Pcs)
            $table->renameColumn('cycle_time', 'pcs_per_hour');
        });

        // Opsional: Ubah tipe data jadi Integer jika sebelumnya Decimal
        Schema::table('product_routings', function (Blueprint $table) {
            $table->integer('pcs_per_hour')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_routings', function (Blueprint $table) {
            $table->renameColumn('pcs_per_hour', 'cycle_time');
        });
    }
};
