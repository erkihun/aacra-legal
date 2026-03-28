<?php

declare(strict_types=1);

use App\Enums\TeamType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_am');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('leader_user_id')->nullable()->index();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_am');
            $table->string('type')->default(TeamType::ADMINISTRATION->value);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('advisory_categories', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_am');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('case_types', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_am');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('courts', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_am');
            $table->string('level')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', static function (Blueprint $table): void {
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });

        Schema::table('teams', static function (Blueprint $table): void {
            $table->foreign('leader_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teams', static function (Blueprint $table): void {
            $table->dropForeign(['leader_user_id']);
        });

        Schema::table('users', static function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['team_id']);
        });

        Schema::dropIfExists('courts');
        Schema::dropIfExists('case_types');
        Schema::dropIfExists('advisory_categories');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('departments');
    }
};
