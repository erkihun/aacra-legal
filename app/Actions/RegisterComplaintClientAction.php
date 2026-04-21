<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Models\User;

class RegisterComplaintClientAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): User
    {
        $user = User::query()->create([
            'branch_id' => $attributes['branch_id'] ?? null,
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'] ?? null,
            'locale' => LocaleCode::ENGLISH,
            'is_active' => true,
            'password' => $attributes['password'],
        ]);

        $user->assignRole(SystemRole::COMPLAINT_CLIENT->value);

        return $user;
    }
}
