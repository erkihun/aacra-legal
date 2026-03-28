<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('attachable');
            $table->uuid('uploaded_by_id')->index();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('uploaded_by_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('comments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('commentable');
            $table->uuid('user_id')->index();
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('attachments');
    }
};
