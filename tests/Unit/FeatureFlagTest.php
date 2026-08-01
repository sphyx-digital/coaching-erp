<?php

namespace Tests\Unit;

use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_unknown_flag_is_off(): void
    {
        $this->assertFalse(feature('this_flag_does_not_exist'));
    }

    public function test_known_flag_reads_config(): void
    {
        config()->set('client.features.online_payments', true);
        $this->assertTrue(feature('online_payments'));

        config()->set('client.features.online_payments', false);
        $this->assertFalse(feature('online_payments'));
    }

    public function test_client_setting_helper_reads_config(): void
    {
        config()->set('client.institute_name', 'Bright Minds');
        $this->assertSame('Bright Minds', client_setting('institute_name'));
    }
}
