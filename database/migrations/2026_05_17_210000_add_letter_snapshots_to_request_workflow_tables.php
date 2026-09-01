<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisory_requests', static function (Blueprint $table): void {
            if (! Schema::hasColumn('advisory_requests', 'letter_snapshot')) {
                $table->json('letter_snapshot')->nullable()->after('letter_template_id');
            }
        });

        Schema::table('lawsuit_filing_requests', static function (Blueprint $table): void {
            if (! Schema::hasColumn('lawsuit_filing_requests', 'letter_snapshot')) {
                $table->json('letter_snapshot')->nullable()->after('letter_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('advisory_requests', static function (Blueprint $table): void {
            if (Schema::hasColumn('advisory_requests', 'letter_snapshot')) {
                $table->dropColumn('letter_snapshot');
            }
        });

        Schema::table('lawsuit_filing_requests', static function (Blueprint $table): void {
            if (Schema::hasColumn('lawsuit_filing_requests', 'letter_snapshot')) {
                $table->dropColumn('letter_snapshot');
            }
        });
    }
};
