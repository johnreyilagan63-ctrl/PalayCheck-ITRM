<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('full_name', 120);
                $table->string('username', 60)->unique();
                $table->string('email', 120)->unique();
                $table->string('password_hash', 255);
                $table->string('role', 20)->default('farmer');
                $table->string('status', 20)->default('active');
                $table->rememberToken();
                $table->timestamp('created_at')->nullable();

                $table->index('created_at');
            });

            return;
        }

        // Keep compatibility with databases created by the Flask/PHP versions,
        // which do not have Laravel's remember_token column.
        if (! Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->rememberToken();
            });
        }
    }

    public function down(): void
    {
        // Existing PalayCheck databases must survive a rollback.
    }
};
