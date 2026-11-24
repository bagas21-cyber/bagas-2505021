<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_can_be_run_more_than_once_without_duplicate_key_errors(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@umkmku.test')->firstOrFail();

        $this->assertSame(1, User::query()->where('email', 'admin@umkmku.test')->count());
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
