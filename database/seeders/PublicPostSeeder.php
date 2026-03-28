<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LocaleCode;
use App\Enums\PublicPostStatus;
use App\Models\PublicPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PublicPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('email', 'director@ldms.test')->first();

        $posts = [
            [
                'title' => 'How Departments Should Prepare Legal Advice Requests',
                'summary' => 'A practical guide for submitting complete legal advisory requests with the right documents, dates, and questions for faster review.',
                'body' => "Departments should submit requests with a clear subject, business context, the legal question to be answered, and any attached supporting documents.\n\nWhere an urgent decision is required, the request should explain the operational deadline and identify the responsible focal person.\n\nThe Legal Department will review the request through the established approval chain before assignment to the advisory team.",
                'locale' => LocaleCode::ENGLISH->value,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Court Hearing Preparation Checklist for Institutional Focal Persons',
                'summary' => 'A short institutional checklist to help departments prepare records and evidence before upcoming litigation hearings.',
                'body' => "Before a hearing date, the responsible department should confirm the factual background, provide original records where needed, and identify any subject matter focal persons.\n\nTimely coordination with the assigned legal expert reduces last-minute delays and improves the quality of the institutional response.\n\nDepartments should also flag any urgent settlement, compliance, or operational risk considerations in advance.",
                'locale' => LocaleCode::ENGLISH->value,
                'published_at' => now()->subDays(12),
            ],
            [
                'title' => 'New Internal Practice Note on Recording Verbal Legal Advice',
                'summary' => 'Verbal legal advice must still be recorded in the system with a date, summary, requester, responsible expert, and follow-up note.',
                'body' => "The Legal Department continues to recognize verbal legal advice where an operational issue requires immediate attention.\n\nHowever, every verbal opinion must be recorded in the system to preserve accountability, institutional memory, and auditability.\n\nRequesting departments should confirm the summary and follow-up actions after the advice is issued.",
                'locale' => LocaleCode::ENGLISH->value,
                'published_at' => now()->subDays(18),
            ],
        ];

        foreach ($posts as $post) {
            PublicPost::query()->updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                [
                    'author_id' => $author?->id,
                    'title' => $post['title'],
                    'summary' => $post['summary'],
                    'body' => $post['body'],
                    'cover_image_path' => null,
                    'status' => PublicPostStatus::PUBLISHED,
                    'locale' => $post['locale'],
                    'published_at' => $post['published_at'],
                ],
            );
        }
    }
}
