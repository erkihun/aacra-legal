<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_templates', static function (Blueprint $table): void {
            if (! Schema::hasColumn('letter_templates', 'header_image_path')) {
                $table->string('header_image_path')->nullable()->after('orientation');
            }

            if (! Schema::hasColumn('letter_templates', 'footer_image_path')) {
                $table->string('footer_image_path')->nullable()->after('header_image_path');
            }

            if (! Schema::hasColumn('letter_templates', 'reference_prefix')) {
                $table->string('reference_prefix', 50)->nullable()->after('reference_label');
            }

            if (! Schema::hasColumn('letter_templates', 'reference_start_number')) {
                $table->unsignedInteger('reference_start_number')->default(1)->after('reference_prefix');
            }

            if (! Schema::hasColumn('letter_templates', 'current_reference_number')) {
                $table->unsignedInteger('current_reference_number')->default(0)->after('reference_start_number');
            }

            if (! Schema::hasColumn('letter_templates', 'numbering_config')) {
                $table->json('numbering_config')->nullable()->after('layout_config');
            }
        });

        if (! Schema::hasTable('letters')) {
            Schema::create('letters', static function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('template_id')->constrained('letter_templates')->restrictOnDelete();
                $table->string('reference_number')->unique();
                $table->date('letter_date');
                $table->string('recipient_name');
                $table->string('recipient_title')->nullable();
                $table->string('recipient_organization')->nullable();
                $table->text('recipient_address')->nullable();
                $table->string('subject')->nullable();
                $table->text('salutation')->nullable();
                $table->longText('body_content');
                $table->longText('closing_content')->nullable();
                $table->longText('signature_block_content')->nullable();
                $table->longText('cc_content')->nullable();
                $table->longText('enclosure_content')->nullable();
                $table->string('header_image_path_snapshot')->nullable();
                $table->string('footer_image_path_snapshot')->nullable();
                $table->string('language', 10)->default('en');
                $table->string('page_size', 10)->default('A4');
                $table->string('orientation', 20)->default('portrait');
                $table->string('status', 20)->default('draft');
                $table->json('layout_config')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('letters');

        Schema::table('letter_templates', static function (Blueprint $table): void {
            foreach ([
                'header_image_path',
                'footer_image_path',
                'reference_prefix',
                'reference_start_number',
                'current_reference_number',
                'numbering_config',
            ] as $column) {
                if (Schema::hasColumn('letter_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
