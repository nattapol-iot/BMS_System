<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->string('icon')->default('fa-cog');
            $table->string('color')->default('#3b82f6');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('equipment_categories'); }
};
