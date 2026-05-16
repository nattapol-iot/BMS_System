<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete()->after('id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('department')->nullable()->after('avatar');
            $table->enum('status', ['active', 'inactive', 'locked'])->default('active')->after('department');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('locale', 5)->default('th')->after('last_login_at');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role_id','phone','avatar','department','status','last_login_at','locale']);
        });
    }
};
