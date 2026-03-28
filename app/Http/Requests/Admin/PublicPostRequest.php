<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\LocaleCode;
use App\Enums\PublicPostStatus;
use App\Models\PublicPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicPostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $slug = $this->filled('slug')
            ? Str::slug((string) $this->input('slug'))
            : Str::slug((string) $this->input('title'));

        $this->merge([
            'title' => $this->filled('title') ? trim((string) $this->input('title')) : null,
            'slug' => $slug,
            'summary' => $this->filled('summary') ? trim((string) $this->input('summary')) : null,
        ]);
    }

    public function authorize(): bool
    {
        $publicPost = $this->route('public_post') ?? $this->route('publicPost');

        if ($publicPost instanceof PublicPost) {
            return $this->user()?->can('update', $publicPost) ?? false;
        }

        return $this->user()?->can('create', PublicPost::class) ?? false;
    }

    public function rules(): array
    {
        /** @var PublicPost|null $publicPost */
        $publicPost = $this->route('public_post') ?? $this->route('publicPost');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique(PublicPost::class, 'slug')->ignore($publicPost?->id),
            ],
            'summary' => ['required', 'string', 'max:1000'],
            'body' => ['required', 'string', 'min:20'],
            'status' => ['required', Rule::enum(PublicPostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'locale' => ['nullable', Rule::in(array_column(LocaleCode::cases(), 'value'))],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('public_posts.fields.title'),
            'slug' => __('public_posts.fields.slug'),
            'summary' => __('public_posts.fields.summary'),
            'body' => __('public_posts.fields.body'),
            'status' => __('public_posts.fields.status'),
            'published_at' => __('public_posts.fields.published_at'),
            'locale' => __('public_posts.fields.locale'),
            'cover_image' => __('public_posts.fields.cover_image'),
        ];
    }
}
