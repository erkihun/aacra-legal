<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', static function (Blueprint $table): void {
            $table->text('description_en')->nullable()->after('guard_name');
            $table->text('description_am')->nullable()->after('description_en');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', static function (Blueprint $table): void {
            $table->dropColumn(['description_en', 'description_am']);
        });
    }
};
