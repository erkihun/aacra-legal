<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_templates', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('document_type')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('page_size', 10)->default('A4');
            $table->string('orientation', 20)->default('portrait');
            $table->string('reference_label')->nullable();
            $table->text('subject_template')->nullable();
            $table->longText('header_content')->nullable();
            $table->longText('recipient_block_template')->nullable();
            $table->longText('salutation_template')->nullable();
            $table->longText('body_content')->nullable();
            $table->longText('closing_content')->nullable();
            $table->longText('signature_block_content')->nullable();
            $table->longText('footer_content')->nullable();
            $table->longText('cc_content')->nullable();
            $table->longText('enclosure_content')->nullable();
            $table->json('layout_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_templates');
    }
};
