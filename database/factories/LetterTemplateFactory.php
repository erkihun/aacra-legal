<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LetterTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LetterTemplate>
 */
class LetterTemplateFactory extends Factory
{
    protected $model = LetterTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => strtoupper($this->faker->unique()->lexify('TMPL-????')),
            'document_type' => 'letter',
            'language' => 'en',
            'page_size' => 'A4',
            'orientation' => 'portrait',
            'salutation_template' => 'Dear Sir/Madam,',
            'closing_content' => 'Yours sincerely,',
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
