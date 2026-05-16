<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('energy_meters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('floor_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['electricity','water','gas'])->default('electricity');
            $table->string('unit')->default('kWh');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('energy_meters'); }
};
