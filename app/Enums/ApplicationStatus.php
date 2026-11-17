<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Wishlist = 'wishlist';
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Offer = 'offer';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Wishlist => 'Wishlist',
            self::Applied => 'Applied',
            self::Screening => 'Screening',
            self::Interview => 'Interview',
            self::Offer => 'Offer',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
