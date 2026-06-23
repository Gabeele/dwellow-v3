<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'system@dwellow.app'],
            [
                'name' => 'System',
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ],
        );

        foreach (config('admin.emails') as $email) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => Str::of($email)->before('@')->headline(),
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );
        }

        User::factory()->count(10)->create();
    }
}
