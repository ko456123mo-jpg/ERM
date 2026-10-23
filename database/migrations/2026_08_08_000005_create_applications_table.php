<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('sha256', 64)->index();
            $table->string('package_name')->nullable();
            $table->string('version_name')->nullable();
            $table->bigInteger('file_size');
            $table->enum('status', ['uploaded', 'queued', 'analyzing', 'completed', 'failed'])->default('uploaded');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
