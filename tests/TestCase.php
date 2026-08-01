<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests assert on markup, not built assets. Stub Vite so a missing
        // build manifest never fails a feature test (CI runs before npm build).
        $this->withoutVite();
    }
}
