<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequence_counters', static function (Blueprint $table): void {
            $table->id();
            $table->string('scope');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('next_value')->default(1);

            $table->unique(['scope', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_counters');
    }
};
