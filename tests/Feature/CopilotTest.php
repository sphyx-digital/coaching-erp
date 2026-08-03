<?php

namespace Tests\Feature;

use App\Livewire\Copilot\Copilot;
use App\Models\Institute;
use App\Models\User;
use App\Services\Ai\CopilotService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CopilotTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Institute::create(['name' => 'Acme']);
        $this->admin = tap(User::factory()->create())->assignRole('Institute Admin');
    }

    public function test_copilot_answers_using_the_anthropic_api(): void
    {
        config()->set('services.anthropic.key', 'test-key');
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Your total outstanding is ₹2,24,400.']]], 200),
        ]);

        $answer = app(CopilotService::class)->answer($this->admin, 'What is my outstanding?');
        $this->assertStringContainsString('2,24,400', $answer);

        Http::assertSent(fn ($r) => $r->url() === 'https://api.anthropic.com/v1/messages'
            && $r['model'] !== null
            && str_contains($r['system'], 'DATA SNAPSHOT'));
    }

    public function test_unconfigured_copilot_shows_a_helpful_message(): void
    {
        config()->set('services.anthropic.key', null);

        Livewire::actingAs($this->admin)->test(Copilot::class)
            ->set('question', 'anything')
            ->call('ask')
            ->assertSee('not configured');
    }

    public function test_chat_records_user_and_assistant_turns(): void
    {
        config()->set('services.anthropic.key', 'test-key');
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Hello from the copilot.']]], 200)]);

        Livewire::actingAs($this->admin)->test(Copilot::class)
            ->set('question', 'Summarise this month')
            ->call('ask')
            ->assertSee('Summarise this month')
            ->assertSee('Hello from the copilot')
            ->assertSet('question', '');
    }

    public function test_portal_users_cannot_use_the_copilot(): void
    {
        $portal = tap(User::factory()->create())->assignRole('Student');
        Livewire::actingAs($portal)->test(Copilot::class)->assertForbidden();
    }
}
