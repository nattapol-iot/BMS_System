<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('floor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained('equipment_categories');
            $table->string('manufacturer')->nullable();
            $table->string('model_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->enum('status', ['active','inactive','offline','maintenance'])->default('active');
            $table->integer('health_score')->default(100);
            $table->decimal('runtime_hours', 10, 2)->default(0);
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->enum('protocol', ['BACnet','Modbus','MQTT','API','None'])->default('None');
            $table->timestamp('last_communication')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('equipment'); }
};
