<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PublicPost;
use App\Services\SystemSettingsService;
use Inertia\Inertia;
use Inertia\Response;

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
            'featuredPosts' => PublicPost::query()
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
                    'url' => route('posts.show', $post),
                ]),
            'stats' => [
                'departments' => Department::query()->active()->where('code', '!=', 'LEG')->count(),
                'workflows' => 2,
                'locales' => count($this->settings->supportedLocales()),
            ],
        ]);
    }
}
