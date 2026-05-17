<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // 1. Add equipment_id (nullable FK) so a meter can be tied to a specific device.
        Schema::table('energy_meters', function (Blueprint $table) {
            $table->foreignId('equipment_id')->nullable()->after('floor_id')
                ->constrained('equipment')->nullOnDelete();
            $table->string('status')->default('active')->after('unit');
        });

        // 2. Extend the type enum to include 'solar' for PV generation meters.
        //    MySQL enum change is done via raw ALTER TABLE.
        DB::statement("ALTER TABLE energy_meters MODIFY COLUMN type ENUM('electricity','water','gas','solar') NOT NULL DEFAULT 'electricity'");
    }

    public function down(): void {
        Schema::table('energy_meters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipment_id');
            $table->dropColumn('status');
        });
        DB::statement("ALTER TABLE energy_meters MODIFY COLUMN type ENUM('electricity','water','gas') NOT NULL DEFAULT 'electricity'");
    }
};
