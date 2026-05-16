<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('floor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->integer('priority')->default(5);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('turn_on_time');
            $table->time('turn_off_time')->nullable();
            $table->string('timezone')->default('Asia/Bangkok');
            $table->json('repeat_days')->nullable();
            $table->enum('recurrence', ['daily','weekly','monthly','once'])->default('weekly');
            $table->boolean('holiday_exception')->default(false);
            $table->enum('status', ['active','inactive','disabled'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('schedules'); }
};
