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
        Schema::table('production_plan_details', function (Blueprint $table) {
            // Kolom untuk menyimpan hasil hitung kanban
            $table->integer('calculated_kanban_cards')->after('calculated_manpower')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('production_plan_details', function (Blueprint $table) {
            $table->dropColumn('calculated_kanban_cards');
        });
    }
};
