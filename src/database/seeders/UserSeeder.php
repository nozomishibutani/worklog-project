<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
                [
                    'name' => '西伶奈',
                    'email' => 'user1@test.com',
                    'role' => Role::USER,
                ],
                [
                    'name' => '山田太郎',
                    'email' => 'user2@test.com',
                    'role' => Role::USER,
                ],
                [
                    'name' => '増田一斉',
                    'email' => 'user3@test.com',
                    'role' => Role::USER,
                ],
                [
                    'name' => '山本敬吉',
                    'email' => 'user4@test.com',
                    'role' => Role::USER,
                ],
                [
                    'name' => '秋田朋美',
                    'email' => 'user5@test.com',
                    'role' => Role::USER,
                ],
                [
                    'name' => '中西教夫',
                    'email' => 'user6@test.com',
                    'role' => Role::USER,
                ],
                [
                    'name' => '山田一郎',
                    'email' => 'user7@test.com',
                    'role' => Role::USER,
                ],
                [
                    'name' => '佐藤一二三',
                    'email' => 'admin1@test.com',
                    'role' => Role::ADMIN,
                ],
                [
                    'name' => '佐藤花子',
                    'email' => 'admin2@test.com',
                    'role' => Role::ADMIN,
                ],
            ];

            foreach ($users as $user) {
                User::create([
                    ...$user,
                    'password' => Hash::make('pass1234'),
                    'email_verified_at' => now(),
                ]);
            }
                }
}
