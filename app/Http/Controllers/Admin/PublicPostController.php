<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\PersistPublicPostAction;
use App\Actions\UpdatePublicPostStatusAction;
use App\Enums\LocaleCode;
use App\Enums\PublicPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublicPostRequest;
use App\Http\Requests\Admin\PublicPostStatusRequest;
use App\Models\PublicPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PublicPost::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,published'],
            'locale' => ['nullable', 'in:en,am'],
        ]);

        $posts = PublicPost::query()
            ->with('author:id,name')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['locale'] ?? null, fn ($query, string $locale) => $query->where('locale', $locale))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (PublicPost $post) => $this->serializePost($post, $request->user()?->can('update', $post) ?? false));

        return Inertia::render('Admin/PublicPosts/Index', [
            'filters' => $filters,
            'posts' => $posts,
            'can' => [
                'create' => $request->user()?->can('create', PublicPost::class) ?? false,
            ],
            'statusOptions' => collect(PublicPostStatus::cases())->map(fn (PublicPostStatus $status) => [
                'value' => $status->value,
                'label' => __("public_posts.status.{$status->value}"),
            ])->values(),
            'localeOptions' => collect(LocaleCode::cases())->map(fn (LocaleCode $locale) => [
                'value' => $locale->value,
                'label' => $locale->label(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PublicPost::class);

        return Inertia::render('Admin/PublicPosts/Form', [
            'postItem' => null,
            'canDelete' => false,
            'statusOptions' => $this->statusOptions(),
            'localeOptions' => $this->localeOptions(),
        ]);
    }

    public function store(PublicPostRequest $request, PersistPublicPostAction $action): RedirectResponse
    {
        $publicPost = $action->execute($request->validated(), $request->user());

        return to_route('public-posts.edit', $publicPost)->with('success', __('public_posts.created'));
    }

    public function show(PublicPost $publicPost): Response
    {
        $this->authorize('view', $publicPost);

        return Inertia::render('Admin/PublicPosts/Show', [
            'postItem' => $this->serializePost($publicPost->load('author'), request()->user()?->can('update', $publicPost) ?? false, includeBody: true),
            'can' => [
                'update' => request()->user()?->can('update', $publicPost) ?? false,
                'delete' => request()->user()?->can('delete', $publicPost) ?? false,
                'publish' => request()->user()?->can('publish', $publicPost) ?? false,
            ],
        ]);
    }

    public function edit(PublicPost $publicPost): Response
    {
        $this->authorize('update', $publicPost);

        return Inertia::render('Admin/PublicPosts/Form', [
            'postItem' => $this->serializePost($publicPost->load('author'), true, includeBody: true),
            'canDelete' => request()->user()?->can('delete', $publicPost) ?? false,
            'statusOptions' => $this->statusOptions(),
            'localeOptions' => $this->localeOptions(),
        ]);
    }

    public function update(PublicPostRequest $request, PublicPost $publicPost, PersistPublicPostAction $action): RedirectResponse
    {
        $action->execute($request->validated(), $request->user(), $publicPost);

        return to_route('public-posts.edit', $publicPost)->with('success', __('public_posts.updated'));
    }

    public function destroy(PublicPost $publicPost): RedirectResponse
    {
        $this->authorize('delete', $publicPost);

        if ($publicPost->cover_image_path !== null) {
            Storage::disk('public')->delete($publicPost->cover_image_path);
        }

        $publicPost->delete();

        return to_route('public-posts.index')->with('success', __('public_posts.deleted'));
    }

    public function publish(PublicPostStatusRequest $request, PublicPost $publicPost, UpdatePublicPostStatusAction $action): RedirectResponse
    {
        $action->publish($publicPost, $request->string('published_at')->toString() ?: null);

        return back()->with('success', __('public_posts.published'));
    }

    public function unpublish(PublicPost $publicPost, UpdatePublicPostStatusAction $action): RedirectResponse
    {
        $this->authorize('publish', $publicPost);

        $action->unpublish($publicPost);

        return back()->with('success', __('public_posts.unpublished'));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePost(PublicPost $post, bool $canManage, bool $includeBody = false): array
    {
        return [
            'id' => $post->id,
            'route_key' => $post->getRouteKey(),
            'title' => $post->title,
            'slug' => $post->slug,
            'summary' => $post->summary,
            'body' => $includeBody ? $post->body : null,
            'status' => $post->status?->value,
            'published_at' => $post->published_at?->toIso8601String(),
            'locale' => $post->locale,
            'author' => $post->author?->name,
            'cover_image_url' => $post->coverImageUrl(),
            'public_url' => $post->status === PublicPostStatus::PUBLISHED ? route('posts.show', $post) : null,
            'can_manage' => $canManage,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(PublicPostStatus::cases())->map(fn (PublicPostStatus $status) => [
            'value' => $status->value,
            'label' => __("public_posts.status.{$status->value}"),
        ])->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function localeOptions(): array
    {
        return collect(LocaleCode::cases())->map(fn (LocaleCode $locale) => [
            'value' => $locale->value,
            'label' => $locale->label(),
        ])->all();
    }
}
