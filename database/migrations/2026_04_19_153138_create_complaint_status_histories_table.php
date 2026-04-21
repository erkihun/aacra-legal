<?php

declare(strict_types=1);

use App\Enums\ComplaintStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_status_histories', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('complaint_id')->index();
            $table->uuid('actor_id')->nullable()->index();
            $table->string('from_status')->nullable();
            $table->string('to_status')->default(ComplaintStatus::SUBMITTED->value);
            $table->string('action')->index();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('acted_at');

            $table->foreign('complaint_id')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_status_histories');
    }
};
