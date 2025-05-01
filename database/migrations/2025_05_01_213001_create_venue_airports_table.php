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
        Schema::create('venue_airport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->foreignId('airport_id')->constrained()->onDelete('cascade');
            $table->float('distance_miles');
            $table->boolean('is_nearest')->default(false);
            $table->timestamps();
            
            $table->unique(['venue_id', 'airport_id']);
            $table->index(['venue_id', 'distance_miles']);
            $table->index('is_nearest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_airport');
    }
};
