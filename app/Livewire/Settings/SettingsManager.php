<?php

namespace App\Livewire\Settings;

use App\Models\FeatureFlag;
use App\Services\Audit\AuditLogger;
use App\Support\Contrast;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsManager extends Component
{
    public string $institute_name = '';

    public string $gstin = '';

    public string $brand_hue = '#6366f1';

    public string $action_color = '#4338ca';

    /** @var array<string,bool> */
    public array $features = [];

    public bool $saved = false;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('settings.view'), 403);

        $this->institute_name = (string) client_setting('institute_name');
        $this->gstin = (string) client_setting('gstin');
        $this->brand_hue = (string) client_setting('brand_hue');
        $this->action_color = (string) client_setting('action_color');

        foreach (array_keys(config('client.features', [])) as $flag) {
            $this->features[$flag] = feature($flag);
        }
    }

    public function save(AuditLogger $audit): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);

        $this->validate([
            'institute_name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'brand_hue' => ['required', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'action_color' => ['required', 'regex:/^#([0-9a-fA-F]{6})$/'],
        ]);

        // The action colour must stay accessible (WCAG AA vs white text).
        if (! Contrast::passesAaOnWhiteText($this->action_color)) {
            $ratio = Contrast::ratio($this->action_color, '#ffffff');
            $this->addError('action_color', sprintf(
                'This colour fails AA contrast with white text (%.2f:1, needs 4.5:1). Choose a darker colour.',
                $ratio
            ));

            return;
        }

        $settings = client_settings();
        $before = [
            'institute_name' => client_setting('institute_name'),
            'brand_hue' => client_setting('brand_hue'),
            'action_color' => client_setting('action_color'),
        ];

        $settings->set('institute_name', $this->institute_name);
        $settings->set('gstin', $this->gstin);
        $settings->set('brand_hue', $this->brand_hue);
        $settings->set('action_color', $this->action_color);

        foreach ($this->features as $flag => $enabled) {
            FeatureFlag::updateOrCreate(['key' => $flag], ['enabled' => (bool) $enabled]);
        }

        $audit->log('settings.updated', before: $before, after: [
            'institute_name' => $this->institute_name,
            'brand_hue' => $this->brand_hue,
            'action_color' => $this->action_color,
        ]);

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.settings.settings-manager');
    }
}
