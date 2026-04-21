<?php

namespace Database\Factories;

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
        return [
        'work_date' =>  now()->format('Y-m-d'),
        ];
    }
}
