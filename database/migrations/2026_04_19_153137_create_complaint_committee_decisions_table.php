<?php

declare(strict_types=1);

use App\Enums\ComplaintCommitteeOutcome;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_committee_decisions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('complaint_id')->index();
            $table->uuid('committee_actor_id')->index();
            $table->longText('investigation_notes')->nullable();
            $table->string('decision_summary');
            $table->longText('decision_detail');
            $table->timestamp('decision_date');
            $table->string('outcome')->default(ComplaintCommitteeOutcome::UPHELD->value)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complaint_id')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('committee_actor_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_committee_decisions');
    }
};
