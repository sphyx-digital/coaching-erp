<?php

namespace App\Livewire\Website;

use App\Models\Branch;
use App\Models\Course;
use App\Support\ClientSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Lightweight CMS for the public website: global content (client_settings),
 * course web details, and quick publish toggles for branches and courses.
 * Branch web details are edited in full on the Branches screen.
 */
#[Layout('layouts.app')]
class WebsiteManager extends Component
{
    /** Global content keys stored in client_settings. */
    public const KEYS = [
        'site_published', 'site_headline', 'site_subhead', 'site_about', 'site_hero_image',
        'site_cta_label', 'site_phone', 'site_email', 'site_address',
        'social_facebook', 'social_instagram', 'social_youtube',
        'site_seo_title', 'site_seo_description',
    ];

    public const LEVELS = ['Foundation', 'Class 9-10', 'Class 11-12', 'JEE', 'NEET', 'Board Prep', 'Crash Course', 'Olympiad'];

    public const MODES = ['offline' => 'Offline', 'online' => 'Online', 'hybrid' => 'Hybrid'];

    /** @var array<string,mixed> */
    public array $site = [];

    public bool $showCourse = false;

    public ?int $editingCourse = null;

    /** @var array<string,mixed> */
    public array $course = [];

    public string $highlightsText = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('settings.view') || Auth::user()?->hasAllBranchAccess(), 403);

        $settings = app(ClientSettings::class);
        foreach (self::KEYS as $k) {
            $this->site[$k] = $settings->get($k, $k === 'site_cta_label' ? 'Enquire now' : '');
        }
        $this->site['site_published'] = (bool) ($this->site['site_published'] ?: false);
    }

    public function saveGlobal(): void
    {
        abort_unless(Auth::user()?->can('settings.update') || Auth::user()?->hasAllBranchAccess(), 403);

        $this->validate([
            'site.site_headline' => ['nullable', 'string', 'max:160'],
            'site.site_email' => ['nullable', 'email', 'max:150'],
            'site.site_phone' => ['nullable', 'string', 'max:20'],
            'site.site_seo_description' => ['nullable', 'string', 'max:300'],
        ]);

        $settings = app(ClientSettings::class);
        foreach (self::KEYS as $k) {
            $val = $this->site[$k] ?? '';
            if ($k === 'site_published') {
                $settings->set($k, $val ? '1' : '', 'bool');
            } else {
                $settings->set($k, (string) $val, 'string');
            }
        }

        session()->flash('site_saved', true);
    }

    public function toggleBranch(int $id): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $branch = Branch::findOrFail($id);
        $branch->is_published = ! $branch->is_published;
        $branch->save();
    }

    public function toggleCourse(int $id): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $course = Course::findOrFail($id);
        $course->is_published = ! $course->is_published;
        $course->save();
    }

    public function editCourse(int $id): void
    {
        $c = Course::findOrFail($id);
        $this->editingCourse = $id;
        $this->course = [
            'slug' => $c->slug ?: Str::slug($c->name),
            'tagline' => $c->tagline ?? '',
            'level' => $c->level ?? '',
            'mode' => $c->mode ?? 'offline',
            'description' => $c->description ?? '',
            'hero_image' => $c->hero_image ?? '',
            'fee_from' => $c->fee_from ? number_format($c->fee_from / 100, 2, '.', '') : '',
            'seo_title' => $c->seo_title ?? '',
            'seo_description' => $c->seo_description ?? '',
            'is_published' => (bool) $c->is_published,
        ];
        $this->highlightsText = collect($c->highlights ?? [])->implode("\n");
        $this->showCourse = true;
    }

    public function saveCourse(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);

        $this->validate([
            'course.slug' => ['required', 'string', 'max:120'],
            'course.tagline' => ['nullable', 'string', 'max:160'],
            'course.fee_from' => ['nullable', 'numeric', 'min:0'],
        ]);

        $c = Course::findOrFail($this->editingCourse);
        $highlights = collect(explode("\n", $this->highlightsText))
            ->map(fn ($l) => trim($l))->filter()->values()->all();

        $c->fill([
            'slug' => Str::slug($this->course['slug']),
            'tagline' => $this->course['tagline'] ?: null,
            'level' => $this->course['level'] ?: null,
            'mode' => $this->course['mode'] ?: 'offline',
            'description' => $this->course['description'] ?: null,
            'hero_image' => $this->course['hero_image'] ?: null,
            'highlights' => $highlights ?: null,
            'fee_from' => $this->course['fee_from'] !== '' ? (int) round(((float) $this->course['fee_from']) * 100) : null,
            'seo_title' => $this->course['seo_title'] ?: null,
            'seo_description' => $this->course['seo_description'] ?: null,
            'is_published' => (bool) ($this->course['is_published'] ?? false),
        ]);
        $c->save();

        $this->showCourse = false;
        session()->flash('site_saved', true);
    }

    public function render()
    {
        return view('livewire.website.website-manager', [
            'branches' => Branch::orderBy('name')->get(),
            'courses' => Course::orderBy('name')->get(),
            'publicUrl' => url('/site'),
        ]);
    }
}
