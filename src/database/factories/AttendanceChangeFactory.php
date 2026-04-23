<?php

namespace Database\Factories;

use App\Models\AttendanceChange;
use Illuminate\Database\Eloquent\Factories\Factory;

use function Symfony\Component\Clock\now;

/**
 * @extends Factory<Attendance>
 */
class AttendanceChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = AttendanceChange::class;

    public function definition(): array
    {
        return [
            'applied_at' => now(),
        ];
    }
}
