<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Provisional = 'provisional';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Provisional => 'Provisional',
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Completed => 'Completed',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function pillVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Withdrawn => 'danger',
            self::OnHold => 'warning',
            self::Completed => 'info',
            default => 'info',
        };
    }

    /** Statuses that count as a live enrollment for duplicate checks. */
    public static function liveValues(): array
    {
        return [self::Provisional->value, self::Active->value, self::OnHold->value];
    }
}
