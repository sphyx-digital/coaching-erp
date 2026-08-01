<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case FollowUp = 'follow_up';
    case Visited = 'visited';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::FollowUp => 'Follow-up',
            self::Visited => 'Visited',
            self::Converted => 'Converted',
            self::Lost => 'Lost',
        };
    }

    /** Status pill variant (colour is paired with the word, never alone). */
    public function pillVariant(): string
    {
        return match ($this) {
            self::Converted => 'success',
            self::Lost => 'danger',
            self::Visited, self::FollowUp => 'warning',
            default => 'info',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Converted;
    }

    /** The working pipeline, in order (excludes terminal Converted/Lost). */
    public static function pipeline(): array
    {
        return [self::New, self::Contacted, self::FollowUp, self::Visited];
    }

    /** May this status move to the target? Converted is terminal; Lost can reopen. */
    public function canTransitionTo(self $to): bool
    {
        if ($this === self::Converted) {
            return false;
        }

        if ($this === self::Lost) {
            return in_array($to, [self::New, self::Contacted], true); // reopen only
        }

        return $to !== self::New; // any working status may move to any other (not back to New)
    }
}
