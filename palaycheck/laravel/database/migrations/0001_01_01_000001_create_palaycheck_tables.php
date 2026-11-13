<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pests')) {
            Schema::create('pests', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->unique();
                $table->string('scientific_name', 160)->nullable();
                $table->text('description')->nullable();
                $table->text('symptoms')->nullable();
                $table->text('effects')->nullable();
                $table->string('status', 20)->default('active');
            });
        }

        if (! Schema::hasTable('advice')) {
            Schema::create('advice', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pest_id')->constrained('pests')->cascadeOnDelete();
                $table->string('title', 160);
                $table->string('category', 40)->default('prevention');
                $table->text('content')->nullable();
                $table->string('status', 20)->default('published');
            });
        }

        if (! Schema::hasTable('learning_materials')) {
            Schema::create('learning_materials', function (Blueprint $table) {
                $table->id();
                $table->string('title', 160);
                $table->string('category', 60)->nullable();
                $table->string('summary', 255)->nullable();
                $table->text('content')->nullable();
                $table->string('status', 20)->default('published');
            });
        }

        if (! Schema::hasTable('detections')) {
            Schema::create('detections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('pest_id')->nullable()->constrained('pests')->nullOnDelete();
                $table->string('label', 120)->nullable();
                $table->float('confidence')->default(0);
                $table->string('severity', 20)->default('low');
                $table->integer('count')->default(1);
                $table->string('image_file', 200)->nullable();
                $table->string('source', 20)->default('camera');
                $table->string('note', 200)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 60)->nullable();
                $table->string('details', 255)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('key', 60)->primary();
                $table->string('value', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Existing PalayCheck databases must survive a rollback.
    }
};
