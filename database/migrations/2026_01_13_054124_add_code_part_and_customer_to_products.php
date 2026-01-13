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
            $table->string('code_part')->nullable()->after('id'); // Disimpan di awal
            $table->string('customer')->nullable()->after('part_name'); // Disimpan setelah nama part
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['code_part', 'customer']);
        });
    }
};
