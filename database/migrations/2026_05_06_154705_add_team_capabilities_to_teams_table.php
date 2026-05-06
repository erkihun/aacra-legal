<?php

declare(strict_types=1);

use App\Enums\TeamType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            if (! Schema::hasColumn('teams', 'supports_advisory')) {
                $table->boolean('supports_advisory')->default(false)->after('type');
            }

            if (! Schema::hasColumn('teams', 'supports_court_case')) {
                $table->boolean('supports_court_case')->default(false)->after('supports_advisory');
            }
        });

        DB::table('teams')
            ->where('type', TeamType::ADVISORY->value)
            ->update([
                'supports_advisory' => true,
                'supports_court_case' => false,
            ]);

        DB::table('teams')
            ->where('type', TeamType::LITIGATION->value)
            ->update([
                'supports_advisory' => false,
                'supports_court_case' => true,
            ]);

        DB::table('teams')
            ->whereNotIn('type', [
                TeamType::ADVISORY->value,
                TeamType::LITIGATION->value,
            ])
            ->update([
                'supports_advisory' => false,
                'supports_court_case' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            if (Schema::hasColumn('teams', 'supports_court_case')) {
                $table->dropColumn('supports_court_case');
            }

            if (Schema::hasColumn('teams', 'supports_advisory')) {
                $table->dropColumn('supports_advisory');
            }
        });
    }
};
