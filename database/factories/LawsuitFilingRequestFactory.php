<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LawsuitRequestStatus;
use App\Models\LawsuitFilingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LawsuitFilingRequest>
 */
class LawsuitFilingRequestFactory extends Factory
{
    protected $model = LawsuitFilingRequest::class;

    public function definition(): array
    {
        return [
            'request_code' => 'LSR-' . date('Y') . '-' . fake()->unique()->numerify('####'),
            'requesting_department_id' => Str::uuid()->toString(),
            'created_by' => User::factory(),
            'reviewed_by' => null,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => LawsuitRequestStatus::SUBMITTED,
            'reviewer_notes' => null,
            'date_submitted' => now()->toDateString(),
        ];
    }
}
