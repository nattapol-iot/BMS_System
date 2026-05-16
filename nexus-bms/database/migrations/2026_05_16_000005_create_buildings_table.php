<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->default('Bangkok');
            $table->string('country')->default('Thailand');
            $table->integer('floors_count')->default(1);
            $table->decimal('total_area', 10, 2)->nullable();
            $table->year('year_built')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('occupancy_count')->default(0);
            $table->integer('occupancy_capacity')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('buildings'); }
};
