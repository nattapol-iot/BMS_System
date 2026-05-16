<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->integer('floor_number');
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->string('floor_plan_image')->nullable();
            $table->timestamps();
            $table->unique(['building_id','floor_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('floors'); }
};
