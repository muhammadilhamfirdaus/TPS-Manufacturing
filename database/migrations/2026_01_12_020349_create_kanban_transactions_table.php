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
        Schema::create('kanban_transactions', function (Blueprint $table) {
            $table->id();
            // Pastikan baris ini ada:
            $table->foreignId('kanban_master_id')->constrained('kanban_masters')->onDelete('cascade');

            $table->enum('transaction_type', ['IN', 'OUT']);
            $table->integer('qty_box');
            $table->integer('qty_total');
            $table->foreignId('user_id')->constrained('users'); // Relasi ke user pen-scan
            $table->timestamp('scanned_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_transactions');
    }
};
