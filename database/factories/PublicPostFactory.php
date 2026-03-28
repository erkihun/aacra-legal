<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LocaleCode;
use App\Enums\PublicPostStatus;
use App\Models\PublicPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PublicPost>
 */
class PublicPostFactory extends Factory
{
    protected $model = PublicPost::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numerify('##'),
            'summary' => $this->faker->paragraph(),
            'body' => implode("\n\n", $this->faker->paragraphs(4)),
            'cover_image_path' => null,
            'status' => PublicPostStatus::PUBLISHED,
            'locale' => LocaleCode::ENGLISH->value,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PublicPostStatus::DRAFT,
            'published_at' => null,
        ]);
    }
}
