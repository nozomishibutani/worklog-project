<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
                [
                    'name' => '山田太郎',
                    'email' => 'user1@test.com',
                    'role' => 'user',
                ],
                [
                    'name' => '西伶奈',
                    'email' => 'user2@test.com',
                    'role' => 'user',
                ],
                [
                    'name' => '増田一斉',
                    'email' => 'user3@test.com',
                    'role' => 'user',
                ],
                [
                    'name' => '山本敬吉',
                    'email' => 'user4@test.com',
                    'role' => 'user',
                ],
                [
                    'name' => '秋田朋美',
                    'email' => 'user5@test.com',
                    'role' => 'user',
                ],
                [
                    'name' => '中西教夫',
                    'email' => 'user6@test.com',
                    'role' => 'user',
                ],
            ];

            foreach ($users as $user) {
                User::create([
                    ...$user,
                    'password' => Hash::make('user1234'),
                    'email_verified_at' => now(),
                ]);
            }
                }
}
