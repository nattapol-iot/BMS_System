<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('equipment', function (Blueprint $table) {
            $table->decimal('x_position', 7, 2)->nullable()->after('room_id');
            $table->decimal('y_position', 7, 2)->nullable()->after('x_position');
        });
    }
    public function down(): void {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['x_position', 'y_position']);
        });
    }
};
