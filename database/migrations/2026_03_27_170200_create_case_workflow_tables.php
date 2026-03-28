<?php

declare(strict_types=1);

use App\Enums\CaseStatus;
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
        Schema::create('legal_cases', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('case_number')->unique();
            $table->string('external_court_file_number')->nullable()->index();
            $table->uuid('court_id')->index();
            $table->uuid('case_type_id')->index();
            $table->uuid('registered_by_id')->index();
            $table->uuid('director_reviewer_id')->nullable()->index();
            $table->uuid('assigned_team_leader_id')->nullable()->index();
            $table->uuid('assigned_legal_expert_id')->nullable()->index();
            $table->string('plaintiff');
            $table->string('defendant');
            $table->string('bench_or_chamber')->nullable();
            $table->string('status')->default(CaseStatus::INTAKE->value)->index();
            $table->string('workflow_stage')->default(WorkflowStage::DIRECTOR->value)->index();
            $table->string('priority')->default(PriorityLevel::MEDIUM->value);
            $table->string('director_decision')->default(DirectorDecision::PENDING->value);
            $table->text('claim_summary');
            $table->text('institution_position')->nullable();
            $table->text('outcome')->nullable();
            $table->text('director_notes')->nullable();
            $table->date('filing_date')->nullable();
            $table->date('next_hearing_date')->nullable();
            $table->date('decision_date')->nullable();
            $table->date('appeal_deadline')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('court_id')->references('id')->on('courts')->restrictOnDelete();
            $table->foreign('case_type_id')->references('id')->on('case_types')->restrictOnDelete();
            $table->foreign('registered_by_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('director_reviewer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_team_leader_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_legal_expert_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('case_assignments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('legal_case_id')->index();
            $table->uuid('assigned_by_id')->index();
            $table->uuid('assigned_to_id')->index();
            $table->string('assignment_role');
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->foreign('legal_case_id')->references('id')->on('legal_cases')->cascadeOnDelete();
            $table->foreign('assigned_by_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('case_hearings', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('legal_case_id')->index();
            $table->uuid('recorded_by_id')->index();
            $table->date('hearing_date');
            $table->date('next_hearing_date')->nullable();
            $table->string('appearance_status')->nullable();
            $table->text('summary');
            $table->text('institution_position')->nullable();
            $table->text('court_decision')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();

            $table->foreign('legal_case_id')->references('id')->on('legal_cases')->cascadeOnDelete();
            $table->foreign('recorded_by_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_hearings');
        Schema::dropIfExists('case_assignments');
        Schema::dropIfExists('legal_cases');
    }
};
