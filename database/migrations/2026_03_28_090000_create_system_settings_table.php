<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('setting_group', 50);
            $table->string('setting_key', 100);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['setting_group', 'setting_key']);
            $table->index('setting_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
