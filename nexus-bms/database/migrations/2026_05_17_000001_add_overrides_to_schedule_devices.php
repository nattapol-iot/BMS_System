<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('schedule_devices', function (Blueprint $table) {
            $table->time('on_time')->nullable()->after('equipment_id');
            $table->time('off_time')->nullable()->after('on_time');
            $table->json('days')->nullable()->after('off_time');
        });
    }
    public function down(): void {
        Schema::table('schedule_devices', function (Blueprint $table) {
            $table->dropColumn(['on_time','off_time','days']);
        });
    }
};
