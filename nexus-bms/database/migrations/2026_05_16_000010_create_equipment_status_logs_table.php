<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('equipment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->integer('health_score')->nullable();
            $table->decimal('value', 15, 4)->nullable();
            $table->string('unit')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('equipment_status_logs'); }
};
