<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $landlord = User::factory()->landlord()->create([
            'name' => 'Test Landlord',
            'email' => 'landlord@example.com',
        ]);

        User::factory()->tenant()->create([
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
        ]);

        Property::factory()->whole()->for($landlord, 'landlord')->create([
            'name' => 'Maple Street Bungalow',
        ]);

        $multiUnit = Property::factory()->multiUnit()->for($landlord, 'landlord')->create([
            'name' => 'Birchwood Apartments',
        ]);

        Unit::factory()->count(3)->for($multiUnit)->create();
    }
}
