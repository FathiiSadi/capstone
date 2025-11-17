<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Fathi',
            'email' => 'fathi@gmail.com',
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Mohammad',
            'email' => 'moh@gmail.com',
        ]);
    }
}
