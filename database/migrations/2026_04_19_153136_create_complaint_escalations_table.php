<?php

declare(strict_types=1);

use App\Enums\ComplaintEscalationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_escalations', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('complaint_id')->index();
            $table->uuid('escalated_by_id')->nullable()->index();
            $table->string('escalation_type')->default(ComplaintEscalationType::AUTO->value)->index();
            $table->text('reason')->nullable();
            $table->timestamp('escalated_at');

            $table->foreign('complaint_id')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('escalated_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_escalations');
    }
};
