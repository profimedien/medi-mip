<?php

namespace Tests;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\StructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StructureSeeder::class);
    }

    protected function location(string $short): Location
    {
        return Location::where('short_name', $short)->firstOrFail();
    }

    protected function department(string $name): Department
    {
        return Department::where('name', $name)->firstOrFail();
    }

    protected function person(string $location = 'Fürth', array $departments = ['Physiotherapie'], array $attrs = []): User
    {
        $user = User::factory()->create(['location_id' => $this->location($location)->id] + $attrs);
        $user->departments()->sync(Department::whereIn('name', $departments)->pluck('id'));
        $user->assignRole('mitarbeiter');

        return $user;
    }
}
