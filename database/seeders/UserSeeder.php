<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table.
     *
     * @return void
     */
    public function run()
    {
        // Define the users
        $users = [
            ['name' => 'User One', 'email' => 'user1@example.com', 'password' => 'password123'],
            ['name' => 'User Two', 'email' => 'user2@example.com', 'password' => 'password123'],
            ['name' => 'User Three', 'email' => 'user3@example.com', 'password' => 'password123'],
            ['name' => 'User Four', 'email' => 'user4@example.com', 'password' => 'password123'],
            ['name' => 'User Five', 'email' => 'user5@example.com', 'password' => 'password123'],
        ];

        // Insert each user
        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make($user['password']), // Hash the password
            ]);
        }
    }
}
