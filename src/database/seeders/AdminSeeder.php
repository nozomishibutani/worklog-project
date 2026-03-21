<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
                [
                    'name' => '佐藤一二三',
                    'email' => 'admin1@test.com',
                    'role' => 'admin',
                ],
                [
                    'name' => '佐藤花子',
                    'email' => 'admin2@test.com',
                    'role' => 'admin',
                ],
            ];

            foreach ($admins as $admin) {
                User::create([
                    ...$admin,
                    'password' => Hash::make('admin1234'),
                    'email_verified_at' => now(),
                ]);
            }
                }
}
