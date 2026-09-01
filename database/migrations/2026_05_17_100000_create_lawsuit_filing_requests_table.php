<?php

declare(strict_types=1);

use App\Enums\LawsuitRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lawsuit_filing_requests', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('request_code')->unique();
            $table->uuid('requesting_department_id')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('reviewed_by')->nullable()->index();

            // Requester portal link (null for internal-user-created requests)
            $table->uuid('requester_account_id')->nullable()->index();
            // Letter template reference used when submitting via portal
            $table->uuid('letter_template_id')->nullable()->index();

            $table->string('subject');
            $table->text('description');

            $table->string('status')->default(LawsuitRequestStatus::SUBMITTED->value)->index();
            $table->text('reviewer_notes')->nullable();
            $table->date('date_submitted');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('requesting_department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            // requester_account_id FK is added in migration 200001 (after requester_accounts table is created)
            $table->foreign('letter_template_id')->references('id')->on('letter_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawsuit_filing_requests');
    }
};
