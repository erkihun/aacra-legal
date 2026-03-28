<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PublicPost;
use App\Services\SystemSettingsService;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Public/Posts/Index', [
            'posts' => PublicPost::query()
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
                    'url' => route('posts.show', $post),
                ]),
        ]);
    }

    public function show(PublicPost $publicPost): Response
    {
        abort_unless(
            $publicPost->status?->value === 'published'
            && $publicPost->published_at !== null
            && $publicPost->published_at->isPast(),
            404,
        );

        $publicPost->load('author:id,name');

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
            'relatedPosts' => PublicPost::query()
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
                    'url' => route('posts.show', $post),
                ]),
        ]);
    }
}
