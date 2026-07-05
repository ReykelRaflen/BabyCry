<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void {
    Schema::create('histories', function (Blueprint $table) {
        $table->id();
        // FK ke Baby
        $table->foreignId('baby_id')->constrained('babies')->onDelete('cascade');
        // FK ke Category
        $table->foreignId('cry_category_id')->constrained('cry_categories')->onDelete('cascade');
        $table->integer('confidence'); // Akurasi AI (%)
        $table->string('audio_path')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
