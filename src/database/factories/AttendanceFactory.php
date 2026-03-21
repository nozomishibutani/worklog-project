<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Attendance;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Attendance::class;

    public function definition(): array
    {
        // Faker 日本語化
        $faker = \Faker\Factory::create('ja_JP');

        return [
        'user_id' => User::factory(),
        'work_date' =>  $faker->text(5),
        'approved_by' => $faker->text(10),
        'approved_at' =>  $faker->sentence(),
        'updated_by' => rand(1, 999999),
        'memo' => rand(1, 4),
        'start_time' => rand(1, 4),
        'end_time' => rand(1, 4),
        ];

    }
}
