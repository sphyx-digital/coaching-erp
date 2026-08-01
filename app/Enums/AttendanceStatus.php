<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function pillVariant(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent => 'danger',
            self::Late => 'warning',
            self::Excused => 'info',
        };
    }

    /** Present and Late count as attended for the percentage. */
    public function countsAsPresent(): bool
    {
        return $this === self::Present || $this === self::Late;
    }

    /** Excused is excluded from the denominator. */
    public function inDenominator(): bool
    {
        return $this !== self::Excused;
    }
}
