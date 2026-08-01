<?php

namespace Tests\Feature;

use App\Livewire\Settings\SettingsManager;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_institute_admin_can_save_settings_and_it_is_audited(): void
    {
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');
        $this->actingAs($admin);

        Livewire::test(SettingsManager::class)
            ->set('institute_name', 'Bright Future Classes')
            ->set('brand_hue', '#6366f1')
            ->set('action_color', '#4338ca')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true);

        $this->assertDatabaseHas('client_settings', ['key' => 'institute_name', 'value' => 'Bright Future Classes']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_inaccessible_action_colour_is_rejected(): void
    {
        $admin = tap(User::factory()->create())->assignRole('Institute Admin');
        $this->actingAs($admin);

        Livewire::test(SettingsManager::class)
            ->set('action_color', '#ffe600') // bright yellow, fails AA on white text
            ->call('save')
            ->assertHasErrors('action_color');
    }

    public function test_non_admin_cannot_open_settings(): void
    {
        $teacher = tap(User::factory()->create())->assignRole('Teacher');

        $this->actingAs($teacher)->get('/settings')->assertForbidden();
    }
}
