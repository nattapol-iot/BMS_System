<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('alarm_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alarm_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', ['triggered','acknowledged','silenced','escalated','resolved']);
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('alarm_events'); }
};
