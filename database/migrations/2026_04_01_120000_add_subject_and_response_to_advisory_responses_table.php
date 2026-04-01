<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisory_responses', static function (Blueprint $table): void {
            $table->string('subject')->nullable()->after('responder_id');
            $table->longText('response')->nullable()->after('subject');
        });

        DB::table('advisory_responses')
            ->select(['id', 'summary', 'advice_text', 'subject', 'response'])
            ->orderBy('created_at')
            ->get()
            ->each(static function (object $row): void {
                $legacySummary = is_string($row->summary) ? trim($row->summary) : '';
                $legacyResponse = is_string($row->advice_text) ? trim($row->advice_text) : '';

                DB::table('advisory_responses')
                    ->where('id', $row->id)
                    ->update([
                        'subject' => $row->subject ?: ($legacySummary !== '' ? Str::limit($legacySummary, 255, '') : null),
                        'response' => $row->response ?: ($legacyResponse !== '' ? $legacyResponse : ($legacySummary !== '' ? $legacySummary : null)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('advisory_responses', static function (Blueprint $table): void {
            $table->dropColumn(['subject', 'response']);
        });
    }
};
