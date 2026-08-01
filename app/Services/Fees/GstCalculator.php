<?php

namespace App\Services\Fees;

/**
 * GST maths on a single line, in integer paise. Deterministic rounding: the tax
 * is rounded once to the paisa, then split so CGST + SGST equals the tax exactly
 * (any odd paisa goes to SGST). No floats leak into stored values.
 */
class GstCalculator
{
    /**
     * @return array{taxable:int, rate_bp:int, cgst:int, sgst:int, igst:int, tax:int, total:int}
     */
    public static function forLine(int $taxable, int $rateBp, bool $interstate): array
    {
        $tax = $rateBp > 0 ? (int) round($taxable * $rateBp / 10000) : 0;

        if ($interstate) {
            $cgst = 0;
            $sgst = 0;
            $igst = $tax;
        } else {
            $cgst = intdiv($tax, 2);
            $sgst = $tax - $cgst; // remainder paisa to SGST, so cgst + sgst == tax
            $igst = 0;
        }

        return [
            'taxable' => $taxable,
            'rate_bp' => $rateBp,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'tax' => $tax,
            'total' => $taxable + $tax,
        ];
    }
}
