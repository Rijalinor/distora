<?php

namespace Database\Seeders;

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
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@distora.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Salesman User
        User::firstOrCreate(
            ['email' => 'sales@distora.com'],
            [
                'name' => 'Salesman',
                'password' => bcrypt('password'),
                'role' => 'salesman',
                'linked_salesman_name' => 'ABDUL HAFIZHUDDIN',
            ]
        );
    }
}
