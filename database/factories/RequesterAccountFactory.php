<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\RequesterAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<RequesterAccount>
 */
class RequesterAccountFactory extends Factory
{
    protected $model = RequesterAccount::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::query()->value('id')
                ?? Department::factory(),
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'job_title' => $this->faker->optional()->jobTitle(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
