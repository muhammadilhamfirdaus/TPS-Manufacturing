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

            // (qty_kanban DIHAPUS karena pakai qty_per_box)

            // 1. Cycle Time (Detik)
            if (!Schema::hasColumn('products', 'cycle_time')) {
                $table->decimal('cycle_time', 10, 2)->default(0)->comment('Cycle Time dalam detik');
            }

            // 2. Lot Size
            if (!Schema::hasColumn('products', 'lot_size')) {
                $table->integer('lot_size')->default(0)->comment('Minimum Lot Size Produksi');
            }

            // 3. Kode Box
            if (!Schema::hasColumn('products', 'kode_box')) {
                $table->string('kode_box')->nullable()->comment('Jenis Packaging');
            }

            // 4. Load Time (Hari)
            if (!Schema::hasColumn('products', 'load_time')) {
                $table->decimal('load_time', 8, 2)->default(0)->comment('Waktu Tunggu');
            }

            // 5. Lead Time (Hari)
            if (!Schema::hasColumn('products', 'lead_time')) {
                $table->decimal('lead_time', 8, 2)->default(0)->comment('Waktu Proses');
            }

            // 6. Kanban Aktif
            if (!Schema::hasColumn('products', 'kanban_aktif')) {
                $table->integer('kanban_aktif')->default(0)->comment('Kartu eksisting');
            }

            // 7. Stock Pcs
            if (!Schema::hasColumn('products', 'stock_pcs')) {
                $table->integer('stock_pcs')->default(0)->comment('Stok aktual');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Hapus kolom jika rollback (Hati-hati, data di kolom ini akan hilang)
            $columns = [
                'qty_kanban',
                'cycle_time',
                'lot_size',
                'kode_box',
                'load_time',
                'lead_time',
                'kanban_aktif',
                'stock_pcs'
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};