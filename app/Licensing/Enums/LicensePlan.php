<?php

declare(strict_types=1);

namespace App\Licensing\Enums;

enum LicensePlan: string
{
    case Personal = 'personal';
    case TeamFive = 'team_5';
    case TeamTen = 'team_10';
    case Business = 'business';

    /**
     * @return list<self>
     */
    public static function purchasable(): array
    {
        return [
            self::Personal,
            self::TeamFive,
            self::TeamTen,
        ];
    }

    /**
     * @return list<string>
     */
    public static function purchasableValues(): array
    {
        return array_map(fn (self $plan): string => $plan->value, self::purchasable());
    }

    /**
     * @return array{seat_count: int, activation_limit: int}
     */
    public function defaults(): array
    {
        return match ($this) {
            self::Personal => ['seat_count' => 1, 'activation_limit' => 2],
            self::TeamFive => ['seat_count' => 5, 'activation_limit' => 5],
            self::TeamTen => ['seat_count' => 10, 'activation_limit' => 10],
            self::Business => ['seat_count' => 1, 'activation_limit' => 10],
        };
    }

    public function keyPrefix(): string
    {
        return match ($this) {
            self::Personal => 'PERS',
            self::TeamFive, self::TeamTen => 'TEAM',
            self::Business => 'BUSN',
        };
    }

    /**
     * @return list<string>
     */
    public function features(): array
    {
        return match ($this) {
            self::Personal => [
                'desktop',
                'background_sync',
                'one_click_encrypt_decrypt',
                'multi_project_management',
            ],
            self::TeamFive, self::TeamTen => [
                'desktop',
                'background_sync',
                'one_click_encrypt_decrypt',
                'multi_project_management',
                'shared_license_management',
                'reassignable_seats',
            ],
            self::Business => [
                'desktop',
                'background_sync',
                'one_click_encrypt_decrypt',
                'multi_project_management',
                'shared_license_management',
                'reassignable_seats',
                'offline_activation',
                'priority_support',
                'security_docs',
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::TeamFive => 'Team 5',
            self::TeamTen => 'Team 10',
            self::Business => 'Business',
        };
    }

    public function isPurchasable(): bool
    {
        return in_array($this, self::purchasable(), true);
    }
}
