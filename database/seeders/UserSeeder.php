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
        // Ensure default department exists
        $department = \App\Models\Department::firstOrCreate(
            ['name' => 'Computer Science'],
            ['code' => 'CS']
        );

        $fathi = User::firstOrCreate(
            ['email' => 'fathi@gmail.com'],
            [
                'name' => 'Fathi',
                'role' => 'admin',
                'password' => '$2y$12$VLJp66Z7YF0O6CkceUF3fu.9vCsSlu13KWsDOsYTRqs.FAIddYh3i', // Default hash or factory default
            ]
        );

        // Ensure Fathi has instructor profile and department
        if ($fathi->instructor) {
            $fathi->instructor->departments()->syncWithoutDetaching([$department->id]);
        }

        $moh = User::firstOrCreate(
            ['email' => 'moh@gmail.com'],
            [
                'name' => 'Mohammad',
                'password' => '$2y$12$VLJp66Z7YF0O6CkceUF3fu.9vCsSlu13KWsDOsYTRqs.FAIddYh3i',
            ]
        );

        // Ensure Mohammad has instructor profile and department
        if ($moh->instructor) {
            $moh->instructor->departments()->syncWithoutDetaching([$department->id]);
        }
    }
}
