<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['status','energy','alarm','maintenance'])->default('status');
            $table->json('parameters')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->enum('format', ['pdf','excel'])->default('pdf');
            $table->enum('status', ['pending','generating','completed','failed'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reports'); }
};
