<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->enum('type', ['office','meeting','server','parking','lobby','common','toilet','storage'])->default('office');
            $table->decimal('area', 10, 2)->nullable();
            $table->integer('occupancy_limit')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('rooms'); }
};
