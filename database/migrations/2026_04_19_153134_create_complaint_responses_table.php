<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_responses', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('complaint_id')->index();
            $table->uuid('responder_id')->index();
            $table->uuid('responder_department_id')->nullable()->index();
            $table->string('subject');
            $table->longText('response_content');
            $table->timestamp('responded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complaint_id')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('responder_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('responder_department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_responses');
    }
};
