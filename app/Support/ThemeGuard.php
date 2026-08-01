<?php

namespace App\Support;

use RuntimeException;

/**
 * Boot-time accessibility guard. A client action colour that fails WCAG AA
 * contrast with white text is rejected loudly, so no client instance can ship
 * an inaccessible theme. Called from ClientServiceProvider::boot().
 */
class ThemeGuard
{
    /**
     * Throw if the action colour does not meet AA (>= 4.5:1) with white text.
     */
    public static function verify(string $actionColor): void
    {
        $ratio = Contrast::ratio($actionColor, '#ffffff');

        if ($ratio < Contrast::AA_TEXT) {
            throw new RuntimeException(sprintf(
                'Client action colour %s fails WCAG AA contrast with white text '.
                '(%.2f:1, needs %.1f:1). Set CLIENT_ACTION_COLOR to a darker, accessible colour.',
                $actionColor,
                $ratio,
                Contrast::AA_TEXT
            ));
        }
    }
}
