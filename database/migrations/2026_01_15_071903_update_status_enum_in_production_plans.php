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
        // Kita gunakan Raw SQL karena mengubah ENUM di Laravel agak rumit
        DB::statement("ALTER TABLE production_plans MODIFY COLUMN status ENUM('DRAFT', 'CONFIRMED', 'COMPLETED', 'AUTO-MRP') NOT NULL DEFAULT 'DRAFT'");
    }

    public function down()
    {
        // Kembalikan ke asal (Opsional)
        DB::statement("ALTER TABLE production_plans MODIFY COLUMN status ENUM('DRAFT', 'CONFIRMED', 'COMPLETED') NOT NULL DEFAULT 'DRAFT'");
    }
};
