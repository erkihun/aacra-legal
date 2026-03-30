<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PublicPost;
use App\Services\SystemSettingsService;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PublicHomeController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function __invoke(): Response
    {
        $content = $this->settings->publicWebsiteContent();

        return Inertia::render('Public/Home', [
            'content' => $content,
            'slides' => $this->settings->publicWebsiteSlides(),
            'featuredPosts' => $this->featuredPosts(),
            'stats' => [
                'departments' => $this->activeDepartmentCount(),
                'workflows' => 2,
                'locales' => count($this->settings->supportedLocales()),
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function featuredPosts(): array
    {
        if (! PublicPost::tableExists()) {
            return [];
        }

        try {
            return PublicPost::query()
                ->with('author:id,name')
                ->published()
                ->where(function ($query): void {
                    $query
                        ->whereNull('locale')
                        ->orWhereIn('locale', array_unique([
                            app()->getLocale(),
                            $this->settings->fallbackLocale(),
                        ]));
                })
                ->latest('published_at')
                ->limit(3)
                ->get()
                ->map(fn (PublicPost $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'summary' => $post->summary,
                    'published_at' => $post->published_at?->toIso8601String(),
                    'author' => $post->author?->name,
                    'cover_image_url' => $post->coverImageUrl(),
                    'url' => route('posts.show', ['slug' => $post->slug]),
                ])
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    private function activeDepartmentCount(): int
    {
        if (! Department::tableExists()) {
            return 0;
        }

        try {
            return Department::query()
                ->active()
                ->where('code', '!=', 'LEG')
                ->count();
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }
    }
}
