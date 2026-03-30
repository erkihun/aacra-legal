<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PublicPost;
use App\Services\SystemSettingsService;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PublicPostController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Public/Posts/Index', [
            'posts' => $this->publishedPostsPaginator(),
        ]);
    }

    public function show(string $slug): Response
    {
        abort_unless(PublicPost::tableExists(), 404);

        try {
            $publicPost = PublicPost::query()
                ->with('author:id,name')
                ->published()
                ->where('slug', $slug)
                ->firstOrFail();
        } catch (Throwable $exception) {
            report($exception);

            abort(404);
        }

        return Inertia::render('Public/Posts/Show', [
            'post' => [
                'id' => $publicPost->id,
                'title' => $publicPost->title,
                'slug' => $publicPost->slug,
                'summary' => $publicPost->summary,
                'body' => $publicPost->body,
                'published_at' => $publicPost->published_at?->toIso8601String(),
                'author' => $publicPost->author?->name,
                'cover_image_url' => $publicPost->coverImageUrl(),
            ],
            'relatedPosts' => $this->relatedPosts($publicPost),
        ]);
    }

    private function publishedPostsPaginator(): LengthAwarePaginator
    {
        if (! PublicPost::tableExists()) {
            return $this->emptyPostsPaginator();
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
                ->paginate(9)
                ->through(fn (PublicPost $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'summary' => $post->summary,
                    'published_at' => $post->published_at?->toIso8601String(),
                    'author' => $post->author?->name,
                    'cover_image_url' => $post->coverImageUrl(),
                    'url' => route('posts.show', ['slug' => $post->slug]),
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->emptyPostsPaginator();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function relatedPosts(PublicPost $publicPost): array
    {
        try {
            return PublicPost::query()
                ->published()
                ->whereKeyNot($publicPost->getKey())
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
                    'summary' => $post->summary,
                    'published_at' => $post->published_at?->toIso8601String(),
                    'url' => route('posts.show', ['slug' => $post->slug]),
                ])
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    private function emptyPostsPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: 9,
            currentPage: LengthAwarePaginator::resolveCurrentPage(),
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        );
    }
}
