<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\DirectorDecision;
use App\Enums\PriorityLevel;
use App\Enums\WorkflowStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisory_requests', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();
            $table->uuid('department_id')->index();
            $table->uuid('category_id')->index();
            $table->uuid('requester_user_id')->index();
            $table->uuid('director_reviewer_id')->nullable()->index();
            $table->uuid('assigned_team_leader_id')->nullable()->index();
            $table->uuid('assigned_legal_expert_id')->nullable()->index();
            $table->string('subject');
            $table->string('request_type')->default(AdvisoryRequestType::WRITTEN->value);
            $table->string('status')->default(AdvisoryRequestStatus::SUBMITTED->value)->index();
            $table->string('workflow_stage')->default(WorkflowStage::DIRECTOR->value)->index();
            $table->string('priority')->default(PriorityLevel::MEDIUM->value);
            $table->string('director_decision')->default(DirectorDecision::PENDING->value);
            $table->text('description');
            $table->text('director_notes')->nullable();
            $table->text('internal_summary')->nullable();
            $table->date('date_submitted');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('advisory_categories')->restrictOnDelete();
            $table->foreign('requester_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('director_reviewer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_team_leader_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_legal_expert_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('advisory_assignments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('advisory_request_id')->index();
            $table->uuid('assigned_by_id')->index();
            $table->uuid('assigned_to_id')->index();
            $table->string('assignment_role');
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->foreign('advisory_request_id')->references('id')->on('advisory_requests')->cascadeOnDelete();
            $table->foreign('assigned_by_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('advisory_responses', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('advisory_request_id')->index();
            $table->uuid('responder_id')->index();
            $table->string('response_type')->default(AdvisoryRequestType::WRITTEN->value);
            $table->text('summary');
            $table->longText('advice_text')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->timestamp('responded_at');
            $table->timestamps();

            $table->foreign('advisory_request_id')->references('id')->on('advisory_requests')->cascadeOnDelete();
            $table->foreign('responder_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisory_responses');
        Schema::dropIfExists('advisory_assignments');
        Schema::dropIfExists('advisory_requests');
    }
};
