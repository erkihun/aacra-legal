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
        Schema::table('departments', static function (Blueprint $table): void {
            $table->foreignUuid('branch_id')->nullable()->after('name_am')->constrained('branches')->nullOnDelete();
        });

        $defaultBranchId = DB::table('branches')
            ->where('is_head_office', true)
            ->value('id')
            ?? DB::table('branches')->orderBy('created_at')->value('id');

        if ($defaultBranchId !== null) {
            DB::table('departments')
                ->whereNull('branch_id')
                ->update(['branch_id' => $defaultBranchId]);
        }
    }

    public function down(): void
    {
        Schema::table('departments', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
