<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default user (v!t)
        User::factory()->create([
            'name' => 'v!t',
            'email' => 'vit@example.com',
        ]);

        // Seed categories
        $this->call([
            CategorySeeder::class,
            SampleGoalSeeder::class, // Optional: comment out if you want to add goals manually
        ]);
    }
}
