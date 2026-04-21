<?php

declare(strict_types=1);

use App\Enums\SystemRole;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $committeeRole = Role::query()
            ->where('name', SystemRole::COMPLAINT_COMMITTEE->value)
            ->where('guard_name', 'web')
            ->first();

        if ($committeeRole !== null && $committeeRole->hasPermissionTo('complaints.view_all')) {
            $committeeRole->revokePermissionTo('complaints.view_all');
        }
    }

    public function down(): void
    {
        // Non-destructive by design.
    }
};
