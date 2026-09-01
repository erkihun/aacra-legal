<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\DirectorDecision;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvisoryRequest>
 */
class AdvisoryRequestFactory extends Factory
{
    protected $model = AdvisoryRequest::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'request_number' => 'ADV-TEST-' . str_pad((string) $counter++, 5, '0', STR_PAD_LEFT),
            'department_id' => \App\Models\Department::query()->value('id') ?? \Illuminate\Support\Str::uuid()->toString(),
            'category_id' => AdvisoryCategory::factory(),
            'subject' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'status' => AdvisoryRequestStatus::SUBMITTED,
            'workflow_stage' => WorkflowStage::DIRECTOR,
            'director_decision' => DirectorDecision::PENDING,
            'date_submitted' => now()->toDateString(),
        ];
    }
}
