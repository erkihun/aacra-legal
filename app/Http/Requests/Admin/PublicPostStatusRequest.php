<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\PublicPost;
use Illuminate\Foundation\Http\FormRequest;

class PublicPostStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $publicPost = $this->route('public_post') ?? $this->route('publicPost');

        return $publicPost instanceof PublicPost
            ? ($this->user()?->can('publish', $publicPost) ?? false)
            : false;
    }

    public function rules(): array
    {
        return [
            'published_at' => ['nullable', 'date'],
        ];
    }
}
