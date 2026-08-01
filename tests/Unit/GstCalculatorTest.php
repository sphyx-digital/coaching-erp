<?php

namespace Tests\Unit;

use App\Services\Fees\GstCalculator;
use PHPUnit\Framework\TestCase;

class GstCalculatorTest extends TestCase
{
    public function test_in_state_splits_into_cgst_and_sgst(): void
    {
        $g = GstCalculator::forLine(100000, 1800, interstate: false); // ₹1000 @ 18%

        $this->assertSame(9000, $g['cgst']);
        $this->assertSame(9000, $g['sgst']);
        $this->assertSame(0, $g['igst']);
        $this->assertSame(18000, $g['tax']);
        $this->assertSame(118000, $g['total']);
    }

    public function test_out_of_state_is_igst_only(): void
    {
        $g = GstCalculator::forLine(100000, 1800, interstate: true);

        $this->assertSame(0, $g['cgst']);
        $this->assertSame(0, $g['sgst']);
        $this->assertSame(18000, $g['igst']);
        $this->assertSame(118000, $g['total']);
    }

    public function test_odd_paisa_goes_to_sgst_so_the_split_still_sums_to_tax(): void
    {
        $g = GstCalculator::forLine(105, 1800, interstate: false); // tax = round(18.9) = 19

        $this->assertSame(19, $g['tax']);
        $this->assertSame(9, $g['cgst']);
        $this->assertSame(10, $g['sgst']);
        $this->assertSame($g['tax'], $g['cgst'] + $g['sgst']);
    }

    public function test_exempt_line_has_no_tax(): void
    {
        $g = GstCalculator::forLine(50000, 0, interstate: false);

        $this->assertSame(0, $g['tax']);
        $this->assertSame(50000, $g['total']);
    }
}
