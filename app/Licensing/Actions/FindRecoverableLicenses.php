<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FindRecoverableLicenses
{
    /**
     * @return Collection<int, License>
     */
    public function execute(string $email): Collection
    {
        return License::query()
            ->where('status', LicenseStatus::Active->value)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where('purchaser_email', $email)
            ->where(function (Builder $query): void {
                $query->whereNotNull('encrypted_license_key');
            })
            ->with('organization')
            ->withCount('activeActivations')
            ->get();
    }
}
