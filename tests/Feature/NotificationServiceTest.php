<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_app_dispatch_writes_message_log_and_notification(): void
    {
        $user = User::factory()->create();
        $service = new NotificationService();

        $log = $service->toUser($user->id, 'Fees due', 'Your installment is due.');

        $this->assertSame('sent', $log->status);
        $this->assertSame('in_app', $log->channel);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'title' => 'Fees due']);
        $this->assertDatabaseHas('message_logs', ['channel' => 'in_app', 'status' => 'sent']);
    }

    public function test_stubbed_channel_logs_queued_without_failing(): void
    {
        config()->set('client.features.whatsapp', true); // external channel needs its flag
        $service = new NotificationService();

        $log = $service->dispatch('whatsapp', [
            'recipient' => '+919999999999',
            'subject' => 'Reminder',
            'body' => 'Fees due',
        ]);

        $this->assertSame('queued', $log->status);
        $this->assertSame('whatsapp', $log->channel);
        $this->assertDatabaseHas('message_logs', ['channel' => 'whatsapp', 'status' => 'queued']);
    }
}
