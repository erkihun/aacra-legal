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
            $table->uuid('requester_account_id')->nullable()->index()->after('requester_user_id');
            $table->uuid('letter_template_id')->nullable()->index()->after('requester_account_id');

            $table->foreign('requester_account_id')->references('id')->on('requester_accounts')->nullOnDelete();
            $table->foreign('letter_template_id')->references('id')->on('letter_templates')->nullOnDelete();

            // Make requester_user_id nullable so portal submissions (without internal user) are allowed
            $table->uuid('requester_user_id')->nullable()->change();
        });

        // Add FK that could not be defined in the lawsuit_filing_requests table migration
        // because requester_accounts did not exist at that timestamp.
        Schema::table('lawsuit_filing_requests', static function (Blueprint $table): void {
            $table->foreign('requester_account_id')->references('id')->on('requester_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lawsuit_filing_requests', static function (Blueprint $table): void {
            $table->dropForeign(['requester_account_id']);
        });

        Schema::table('advisory_requests', static function (Blueprint $table): void {
            $table->dropForeign(['requester_account_id']);
            $table->dropForeign(['letter_template_id']);
            $table->dropColumn(['requester_account_id', 'letter_template_id']);
            $table->uuid('requester_user_id')->nullable(false)->change();
        });
    }
};
