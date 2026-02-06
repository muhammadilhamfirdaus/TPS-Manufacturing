<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('mpp_adjustments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
        $table->integer('month');
        $table->integer('year');
        $table->integer('helper')->default(0);
        $table->integer('backup')->default(0);
        $table->integer('absensi')->default(0);
        $table->timestamps();
        
        // Mencegah duplikasi data untuk line yang sama di periode yang sama
        $table->unique(['production_line_id', 'month', 'year']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpp_adjustments');
    }
};
