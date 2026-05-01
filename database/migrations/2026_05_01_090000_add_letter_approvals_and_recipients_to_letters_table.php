<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', static function (Blueprint $table): void {
            if (! Schema::hasColumn('letters', 'recipients')) {
                $table->json('recipients')->nullable()->after('recipient_address');
            }

            if (! Schema::hasColumn('letters', 'approval_status')) {
                $table->string('approval_status', 30)->default('draft')->after('status');
            }

            if (! Schema::hasColumn('letters', 'approved_by')) {
                $table->foreignUuid('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('letters', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('letters', 'approved_signature_path_snapshot')) {
                $table->string('approved_signature_path_snapshot')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('letters', 'approved_signer_name_snapshot')) {
                $table->string('approved_signer_name_snapshot')->nullable()->after('approved_signature_path_snapshot');
            }

            if (! Schema::hasColumn('letters', 'approved_signer_title_snapshot')) {
                $table->string('approved_signer_title_snapshot')->nullable()->after('approved_signer_name_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('letters', static function (Blueprint $table): void {
            foreach ([
                'approved_by',
                'recipients',
                'approval_status',
                'approved_at',
                'approved_signature_path_snapshot',
                'approved_signer_name_snapshot',
                'approved_signer_title_snapshot',
            ] as $column) {
                if (Schema::hasColumn('letters', $column)) {
                    if ($column === 'approved_by') {
                        $table->dropConstrainedForeignId($column);

                        continue;
                    }

                    $table->dropColumn($column);
                }
            }
        });
    }
};
