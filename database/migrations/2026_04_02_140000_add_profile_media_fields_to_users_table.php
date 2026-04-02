<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('telegram_chat_id');
            $table->string('signature_path')->nullable()->after('avatar_path');
            $table->string('stamp_path')->nullable()->after('signature_path');
            $table->string('national_id', 16)->nullable()->after('job_title');
            $table->string('telegram_username', 33)->nullable()->after('national_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropColumn([
                'avatar_path',
                'signature_path',
                'stamp_path',
                'national_id',
                'telegram_username',
            ]);
        });
    }
};
