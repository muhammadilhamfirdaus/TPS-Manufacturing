<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_masters', function (Blueprint $table) {
            $table->id();
            
            // INI YANG HILANG:
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->enum('kanban_type', ['FG', 'WIP', 'SUBCONT']); 
            $table->integer('number_of_cards'); 
            $table->string('location_code');
            $table->decimal('daily_demand_forecast', 10, 2); 
            $table->decimal('lead_time_days', 5, 2); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_masters');
    }
};