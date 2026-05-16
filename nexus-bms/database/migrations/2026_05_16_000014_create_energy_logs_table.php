<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('energy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained('energy_meters')->cascadeOnDelete();
            $table->decimal('value', 15, 4);
            $table->decimal('peak_demand', 15, 4)->nullable();
            $table->decimal('power_factor', 5, 4)->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('energy_logs'); }
};
