<?php

declare(strict_types=1);

use App\Enums\ComplaintComplainantType;
use App\Enums\ComplaintStatus;
use App\Enums\PriorityLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('complaint_number')->unique();
            $table->uuid('complainant_user_id')->index();
            $table->uuid('branch_id')->nullable()->index();
            $table->uuid('department_id')->index();
            $table->uuid('assigned_committee_user_id')->nullable()->index();
            $table->string('complainant_type')->default(ComplaintComplainantType::CLIENT->value)->index();
            $table->string('complainant_name');
            $table->string('complainant_email')->nullable()->index();
            $table->string('complainant_phone')->nullable();
            $table->string('subject');
            $table->longText('details');
            $table->string('category')->nullable()->index();
            $table->string('priority')->nullable()->default(PriorityLevel::MEDIUM->value);
            $table->timestamp('submitted_at');
            $table->timestamp('department_response_deadline_at')->nullable()->index();
            $table->timestamp('department_responded_at')->nullable();
            $table->timestamp('forwarded_to_committee_at')->nullable();
            $table->timestamp('committee_review_started_at')->nullable();
            $table->timestamp('committee_decision_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('status')->default(ComplaintStatus::SUBMITTED->value)->index();
            $table->boolean('is_overdue')->default(false)->index();
            $table->boolean('is_escalated')->default(false)->index();
            $table->boolean('is_auto_escalated')->default(false);
            $table->boolean('is_dissatisfied')->default(false);
            $table->text('dissatisfaction_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complainant_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('assigned_committee_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
