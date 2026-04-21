<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', static function (Blueprint $table): void {
            $table->string('region')->nullable()->after('name_am');
            $table->string('city')->nullable()->after('region');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->string('manager_name')->nullable()->after('email');
            $table->text('notes')->nullable()->after('manager_name');
        });
    }

    public function down(): void
    {
        Schema::table('branches', static function (Blueprint $table): void {
            $table->dropColumn([
                'region',
                'city',
                'phone',
                'email',
                'manager_name',
                'notes',
            ]);
        });
    }
};
