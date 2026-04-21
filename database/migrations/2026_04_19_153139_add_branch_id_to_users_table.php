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
        Schema::table('users', static function (Blueprint $table): void {
            $table->uuid('branch_id')->nullable()->after('team_id')->index();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        $headOfficeId = DB::table('branches')->where('code', 'HO')->value('id');

        if (is_string($headOfficeId) && $headOfficeId !== '') {
            DB::table('users')
                ->whereNull('branch_id')
                ->update(['branch_id' => $headOfficeId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
