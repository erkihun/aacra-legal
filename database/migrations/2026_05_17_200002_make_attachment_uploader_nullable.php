<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', static function (Blueprint $table): void {
            // Allow portal (requester_account) uploads where no internal user exists
            $table->uuid('uploaded_by_id')->nullable()->change();
            $table->uuid('requester_uploader_id')->nullable()->index()->after('uploaded_by_id');
            $table->foreign('requester_uploader_id')->references('id')->on('requester_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', static function (Blueprint $table): void {
            $table->dropForeign(['requester_uploader_id']);
            $table->dropColumn('requester_uploader_id');
            $table->uuid('uploaded_by_id')->nullable(false)->change();
        });
    }
};
