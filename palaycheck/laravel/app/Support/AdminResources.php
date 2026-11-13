<?php

namespace App\Support;

use App\Models\Advice;
use App\Models\LearningMaterial;
use App\Models\Pest;
use App\Models\User;

class AdminResources
{
    public static function all(): array
    {
        return [
            'users' => [
                'model' => User::class,
                'title' => 'Users',
                'columns' => [
                    'full_name' => 'Name',
                    'username' => 'Username',
                    'email' => 'Email',
                    'role' => 'Role',
                    'status' => 'Status',
                ],
                'search' => ['full_name', 'username', 'email'],
                'fields' => [
                    'full_name' => ['label' => 'Full name', 'type' => 'text', 'required' => true],
                    'username' => ['label' => 'Username', 'type' => 'text', 'required' => true],
                    'email' => ['label' => 'Email', 'type' => 'email', 'required' => true],
                    'role' => ['label' => 'Role', 'type' => 'select', 'options' => ['farmer', 'admin']],
                    'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active', 'inactive']],
                    'password' => ['label' => 'Password (leave blank to keep)', 'type' => 'password'],
                ],
            ],
            'pests' => [
                'model' => Pest::class,
                'title' => 'Pests',
                'columns' => [
                    'name' => 'Name',
                    'scientific_name' => 'Scientific name',
                    'status' => 'Status',
                ],
                'search' => ['name', 'scientific_name'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'scientific_name' => ['label' => 'Scientific name', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'symptoms' => ['label' => 'Symptoms', 'type' => 'textarea'],
                    'effects' => ['label' => 'Effects', 'type' => 'textarea'],
                    'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active', 'inactive']],
                ],
            ],
            'advice' => [
                'model' => Advice::class,
                'title' => 'Farming Advice',
                'columns' => [
                    'pest.name' => 'Pest',
                    'title' => 'Title',
                    'category' => 'Category',
                    'status' => 'Status',
                ],
                'search' => ['title', 'content'],
                'fields' => [
                    'pest_id' => ['label' => 'Pest', 'type' => 'pest', 'required' => true],
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                    'category' => ['label' => 'Category', 'type' => 'select', 'options' => ['prevention', 'cultural', 'biological', 'chemical']],
                    'content' => ['label' => 'Content', 'type' => 'textarea'],
                    'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['published', 'draft']],
                ],
            ],
            'learning' => [
                'model' => LearningMaterial::class,
                'title' => 'Learning Materials',
                'columns' => [
                    'title' => 'Title',
                    'category' => 'Category',
                    'status' => 'Status',
                ],
                'search' => ['title', 'summary'],
                'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                    'category' => ['label' => 'Category', 'type' => 'text'],
                    'summary' => ['label' => 'Summary', 'type' => 'text'],
                    'content' => ['label' => 'Content', 'type' => 'textarea'],
                    'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['published', 'draft']],
                ],
            ],
        ];
    }

    public static function get(string $resource): ?array
    {
        return self::all()[$resource] ?? null;
    }

    public static function defaults(string $resource): array
    {
        return match ($resource) {
            'users' => ['role' => 'farmer', 'status' => 'active'],
            'pests' => ['status' => 'active'],
            'advice' => ['category' => 'prevention', 'status' => 'published'],
            'learning' => ['status' => 'published'],
            default => [],
        };
    }
}
