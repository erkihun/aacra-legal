<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisory_responses', function (Blueprint $table): void {
            if (! Schema::hasColumn('advisory_responses', 'approval_status')) {
                $table->string('approval_status')->default('pending')->after('responded_at');
            }

            if (! Schema::hasColumn('advisory_responses', 'approved_by')) {
                $table->foreignUuid('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('advisory_responses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        DB::table('advisory_responses')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => 'approved',
                'approved_by' => DB::raw('responder_id'),
                'approved_at' => DB::raw('responded_at'),
            ]);

        DB::table('advisory_responses')
            ->where('approval_status', 'pending')
            ->whereNotNull('responded_at')
            ->whereNull('approved_at')
            ->update([
                'approval_status' => 'approved',
                'approved_by' => DB::raw('responder_id'),
                'approved_at' => DB::raw('responded_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('advisory_responses', function (Blueprint $table): void {
            foreach (['approved_at', 'approved_by', 'approval_status'] as $column) {
                if (! Schema::hasColumn('advisory_responses', $column)) {
                    continue;
                }

                if ($column === 'approved_by') {
                    $table->dropConstrainedForeignId($column);

                    continue;
                }

                $table->dropColumn($column);
            }
        });
    }
};
