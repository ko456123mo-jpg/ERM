<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->string('engine')->default('MobSF');
            $table->string('analysis_type')->default('static');
            $table->enum('risk_level', ['Low', 'Medium', 'High', 'Critical', 'Unknown'])->default('Unknown');
            $table->integer('score')->default(0);
            $table->json('findings_json')->nullable();
            $table->json('permissions_json')->nullable();
            $table->json('components_json')->nullable();
            $table->json('security_issues_json')->nullable();
            $table->json('raw_metadata_json')->nullable();
            $table->enum('status', ['queued', 'analyzing', 'completed', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
