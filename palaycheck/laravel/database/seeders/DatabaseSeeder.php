<?php

namespace Database\Seeders;

use App\Models\Advice;
use App\Models\LearningMaterial;
use App\Models\Pest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/seed.json');
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $adminPassword = null;
        $generatedAdminPassword = null;

        DB::transaction(function () use ($data, &$adminPassword, &$generatedAdminPassword): void {
            foreach ($data['pests'] as [$name, $scientificName, $description, $symptoms, $effects]) {
                $pest = Pest::firstOrCreate(
                    ['name' => $name],
                    [
                        'scientific_name' => $scientificName,
                        'description' => $description,
                        'symptoms' => $symptoms,
                        'effects' => $effects,
                        'status' => 'active',
                    ]
                );

                foreach ($data['advice'][$name] ?? [] as [$category, $title, $content]) {
                    Advice::firstOrCreate(
                        ['pest_id' => $pest->id, 'title' => $title],
                        ['category' => $category, 'content' => $content, 'status' => 'published']
                    );
                }
            }

            foreach ($data['learning'] as [$title, $category, $summary, $content]) {
                LearningMaterial::firstOrCreate(
                    ['title' => $title],
                    [
                        'category' => $category,
                        'summary' => $summary,
                        'content' => $content,
                        'status' => 'published',
                    ]
                );
            }

            Setting::firstOrCreate(['key' => 'threshold'], ['value' => '0.5']);
            Setting::firstOrCreate(['key' => 'model_file'], ['value' => 'palaycheck_best.pt']);

            $admin = User::where('role', 'admin')->orderBy('id')->first();
            $usernameOwner = User::where('username', 'admin')->first();

            if ($admin) {
                if ($usernameOwner && (int) $usernameOwner->id !== (int) $admin->id) {
                    throw new RuntimeException('Cannot set the admin username: another account already uses "admin".');
                }

                if (! $usernameOwner) {
                    $admin->username = 'admin';
                    $admin->save();
                }

                return;
            }

            if ($usernameOwner) {
                throw new RuntimeException('Cannot create the admin account: another account already uses "admin".');
            }

            $adminPassword = env('ADMIN_PASSWORD');
            if (! is_string($adminPassword) || $adminPassword === '') {
                $adminPassword = bin2hex(random_bytes(12));
                $generatedAdminPassword = $adminPassword;
            }

            User::create([
                'full_name' => 'PalayCheck Administrator',
                'username' => 'admin',
                'email' => 'admin@palaycheck.local',
                'password_hash' => Hash::make($adminPassword),
                'role' => 'admin',
                'status' => 'active',
                'created_at' => now(),
            ]);
        });

        if ($generatedAdminPassword !== null && isset($this->command)) {
            $this->command->warn('Admin username: admin');
            $this->command->warn('Admin password (save this now): '.$generatedAdminPassword);
        }
    }
}
