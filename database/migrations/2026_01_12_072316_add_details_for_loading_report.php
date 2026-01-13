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
        Schema::table('products', function (Blueprint $table) {
            $table->string('flow_process')->nullable()->after('part_name'); // Contoh: OP10, BLANKING
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->string('tonnage')->nullable()->after('name'); // Contoh: 45 TON, 25 TON
            $table->string('machine_code')->nullable()->after('name'); // Contoh: 11-P45-43
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('flow_process');
        });
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['tonnage', 'machine_code']);
        });
    }
};
