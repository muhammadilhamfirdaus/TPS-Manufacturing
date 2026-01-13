<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            
            // INI YANG HILANG ATAU SALAH DI KODEMU:
            // Pastikan baris ini ada!
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            
            $table->string('name');
            $table->integer('capacity_per_hour');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};