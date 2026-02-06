<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Menambahkan kolom conveyance
            // Tipe INTEGER (karena satuannya detik)
            // NULLABLE (agar bisa dibedakan antara '0' dengan 'belum diisi')
            // Default NULL (artinya ikut logika default sistem: 3600/900)
            
            $table->integer('conveyance')
                  ->nullable()
                  ->default(null)
                  ->after('kanban_post') // Opsional: meletakkan kolom setelah kanban_post
                  ->comment('Manual input Conveyance dalam detik. Jika NULL, gunakan default logic.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Menghapus kolom jika di-rollback
            $table->dropColumn('conveyance');
        });
    }
};