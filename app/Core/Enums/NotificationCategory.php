<?php

namespace App\Core\Enums;

enum NotificationCategory: string
{
    case BLOG = 'blog';
    case PROMOTIONAL = 'promotional';
    case PRODUCT_TIPS = 'product_tips';
    case RESEARCH = 'research';

    public function label(): string
    {
        return match($this) {
            self::BLOG         => 'Blog & Newsletter',
            self::PROMOTIONAL  => 'Promotional',
            self::PRODUCT_TIPS => 'Product Tips',
            self::RESEARCH     => 'Research & Feedback',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::BLOG         => 'Get updates when new posts are published.',
            self::PROMOTIONAL  => 'Receive promotions, product updates, and offers.',
            self::PRODUCT_TIPS => 'Tips and lifecycle emails to help you get more from Ghostable.',
            self::RESEARCH     => 'Invites to surveys, betas, and feedback sessions.',
        };
    }
}