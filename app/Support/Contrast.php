<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * WCAG 2.1 contrast maths. Used by the boot-time guard that rejects an
 * inaccessible client action colour, and by the Phase 18 accessibility tests.
 */
class Contrast
{
    /** WCAG AA minimum contrast for normal text. */
    public const AA_TEXT = 4.5;

    /** WCAG AA minimum contrast for UI components and large text. */
    public const AA_UI = 3.0;

    /**
     * Contrast ratio between two hex colours (1.0 to 21.0).
     */
    public static function ratio(string $hexA, string $hexB): float
    {
        $l1 = self::relativeLuminance($hexA);
        $l2 = self::relativeLuminance($hexB);
        [$light, $dark] = $l1 >= $l2 ? [$l1, $l2] : [$l2, $l1];

        return ($light + 0.05) / ($dark + 0.05);
    }

    /**
     * Does the colour pass AA contrast with white text behind it?
     */
    public static function passesAaOnWhiteText(string $hex): bool
    {
        return self::ratio($hex, '#ffffff') >= self::AA_TEXT;
    }

    /**
     * WCAG relative luminance of a hex colour.
     */
    public static function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = self::toRgb($hex);

        $channel = static function (float $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }

    /**
     * Parse #rgb or #rrggbb into an [r, g, b] triple.
     *
     * @return array{0:int,1:int,2:int}
     */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new InvalidArgumentException("Invalid hex colour: #{$hex}");
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
