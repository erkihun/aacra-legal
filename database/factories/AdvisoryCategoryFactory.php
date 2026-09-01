<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdvisoryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvisoryCategory>
 */
class AdvisoryCategoryFactory extends Factory
{
    protected $model = AdvisoryCategory::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('CAT-????')),
            'name_en' => $this->faker->words(3, true),
            'name_am' => $this->faker->words(3, true),
            'is_active' => true,
        ];
    }
}
