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
    Schema::create('recommendations', function (Blueprint $table) {
        $table->id();
        // FK ke Category
        $table->foreignId('cry_category_id')->constrained('cry_categories')->onDelete('cascade');
        $table->text('isi'); // Kalimat solusi untuk orang tua
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
