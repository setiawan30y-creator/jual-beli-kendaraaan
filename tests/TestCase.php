<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function seedDemo(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seed([ReferenceDataSeeder::class, DemoSeeder::class]);
    }

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    protected function tenant(string $slug): Tenant
    {
        return Tenant::where('slug', $slug)->firstOrFail();
    }
}
