<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\User;

/**
 * Finds the user's company by name (case-insensitive) or creates it,
 * so a vacancy can be added by just typing the company name.
 */
class ResolveCompany
{
    public function handle(User $user, string $name): Company
    {
        $name = trim($name);

        $existing = $user->companies()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first();

        return $existing ?? $user->companies()->create(['name' => $name]);
    }
}
