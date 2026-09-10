<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $teamCount = Team::count();
        $userCount = User::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($teamCount, Team::count());
        $this->assertSame($userCount, User::count());
    }
}
