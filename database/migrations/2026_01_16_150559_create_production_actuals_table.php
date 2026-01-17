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
        Schema::create('production_actuals', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->foreignId('production_line_id')->constrained('production_lines'); // Di line mana?
            $table->foreignId('product_id')->constrained('products'); // Part apa?
            $table->integer('shift_id')->default(1);

            // Link ke Plan (Opsional, tapi bagus untuk tracking)
            $table->foreignId('production_plan_detail_id')->nullable();

            // Hasil Produksi
            $table->integer('qty_good')->default(0);   // Hasil OK
            $table->integer('qty_reject')->default(0); // Hasil NG (Reject)

            $table->text('notes')->nullable(); // Catatan (misal: Mesin Breakdown)
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_actuals');
    }
};
