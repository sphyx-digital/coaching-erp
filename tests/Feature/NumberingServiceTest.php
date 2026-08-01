<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Institute;
use App\Services\Numbering\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sequential correctness proves the algorithm is gapless and unique. Real
     * concurrency safety comes from the FOR UPDATE row lock on MySQL.
     */
    public function test_numbers_are_unique_and_gapless(): void
    {
        $institute = Institute::create(['name' => 'Acme']);
        $service = new NumberingService();

        $numbers = [];
        for ($i = 0; $i < 25; $i++) {
            $numbers[] = $service->next($institute->id, 'receipt');
        }

        $this->assertCount(25, array_unique($numbers));
        $this->assertSame('RCPT/0001', $numbers[0]);
        $this->assertSame('RCPT/0025', $numbers[24]);
    }

    public function test_series_are_independent_per_scope(): void
    {
        $institute = Institute::create(['name' => 'Acme']);
        $a = Branch::create(['institute_id' => $institute->id, 'name' => 'A', 'code' => 'A']);
        $b = Branch::create(['institute_id' => $institute->id, 'name' => 'B', 'code' => 'B']);
        $service = new NumberingService();

        $a1 = $service->next($institute->id, 'invoice', $a->id);
        $a2 = $service->next($institute->id, 'invoice', $a->id);
        $b1 = $service->next($institute->id, 'invoice', $b->id);

        $this->assertSame('INV/0001', $a1);
        $this->assertSame('INV/0002', $a2);
        $this->assertSame('INV/0001', $b1); // branch B has its own series
    }
}
