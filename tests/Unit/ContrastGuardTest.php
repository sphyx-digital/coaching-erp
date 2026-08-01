<?php

namespace Tests\Unit;

use App\Support\Contrast;
use App\Support\ThemeGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ContrastGuardTest extends TestCase
{
    public function test_black_on_white_is_maximum_contrast(): void
    {
        $this->assertEqualsWithDelta(21.0, Contrast::ratio('#000000', '#ffffff'), 0.1);
    }

    public function test_accessible_action_colour_passes_aa_on_white_text(): void
    {
        $this->assertTrue(Contrast::passesAaOnWhiteText('#4338ca'));

        // Should not throw for the default action colour.
        ThemeGuard::verify('#4338ca');
        $this->addToAssertionCount(1);
    }

    public function test_inaccessible_action_colour_is_rejected(): void
    {
        // Bright yellow behind white text fails AA badly.
        $this->assertFalse(Contrast::passesAaOnWhiteText('#ffe600'));

        $this->expectException(RuntimeException::class);
        ThemeGuard::verify('#ffe600');
    }
}
