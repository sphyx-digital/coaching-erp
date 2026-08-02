<div class="stack">
    <x-page-header title="Website">
        <x-slot:actions>
            <a class="btn" href="{{ $publicUrl }}" target="_blank" rel="noopener">View live site &#8599;</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('site_saved'))
        <div class="alert alert--success" role="status">Saved. Changes are live on the public site.</div>
    @endif

    {{-- Global content -------------------------------------------------- --}}
    <x-card>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:var(--space-3);margin-bottom:var(--space-3);">
            <div>
                <h2 style="margin:0;font-size:16px;">Site content</h2>
                <p class="field__hint" style="margin:2px 0 0;">Headline, about text, contact and SEO for the public marketing site.</p>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;white-space:nowrap;">
                <input type="checkbox" wire:model="site.site_published">
                <span>Site published</span>
            </label>
        </div>

        <div class="form-grid form-grid--2">
            <x-field name="site.site_headline" label="Hero headline" wire:model="site.site_headline" placeholder="India's most trusted JEE &amp; NEET coaching" />
            <x-field name="site.site_cta_label" label="Primary button label" wire:model="site.site_cta_label" placeholder="Enquire now" />
        </div>
        <x-field name="site.site_subhead" label="Hero sub-headline" wire:model="site.site_subhead" placeholder="Small batches, expert faculty, proven results." />

        <div class="field">
            <label class="field__label" for="site_about">About the institute</label>
            <textarea id="site_about" class="input" rows="4" wire:model="site.site_about" placeholder="Tell prospective students and parents who you are."></textarea>
        </div>

        <x-field name="site.site_hero_image" label="Hero image URL" wire:model="site.site_hero_image" placeholder="https://…/hero.jpg" />

        <div class="form-grid form-grid--3">
            <x-field name="site.site_phone" label="Contact phone" wire:model="site.site_phone" />
            <x-field name="site.site_email" label="Contact email" wire:model="site.site_email" />
            <x-field name="site.site_address" label="Address" wire:model="site.site_address" />
        </div>
        <div class="form-grid form-grid--3">
            <x-field name="site.social_facebook" label="Facebook URL" wire:model="site.social_facebook" />
            <x-field name="site.social_instagram" label="Instagram URL" wire:model="site.social_instagram" />
            <x-field name="site.social_youtube" label="YouTube URL" wire:model="site.social_youtube" />
        </div>
        <div class="form-grid form-grid--2">
            <x-field name="site.site_seo_title" label="SEO title" wire:model="site.site_seo_title" />
            <x-field name="site.site_seo_description" label="SEO description" wire:model="site.site_seo_description" />
        </div>

        <div style="margin-top:var(--space-3);">
            <button class="btn btn--primary" wire:click="saveGlobal">Save site content</button>
        </div>
    </x-card>

    {{-- Courses ---------------------------------------------------------- --}}
    <x-card>
        <h2 style="margin:0 0 4px;font-size:16px;">Programmes on the site</h2>
        <p class="field__hint" style="margin:0 0 var(--space-3);">Publish a course to show it on the website. Add web details for a richer programme page.</p>
        @if ($courses->isEmpty())
            <x-state title="No courses yet">Create courses first, then publish them here.</x-state>
        @else
            <x-data-table :head="['Course', 'Level', 'Fee from', 'Status', '']">
                @foreach ($courses as $c)
                    <tr wire:key="wc-{{ $c->id }}">
                        <td><b>{{ $c->name }}</b><div class="field__hint">/{{ $c->slug ?: '—' }}</div></td>
                        <td>{{ $c->level ?: '—' }}</td>
                        <td class="num">{{ $c->fee_from ? paise_to_rupees($c->fee_from) : '—' }}</td>
                        <td>
                            @if ($c->is_published)<x-pill variant="success">Published</x-pill>@else<x-pill variant="muted">Draft</x-pill>@endif
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button class="btn btn--sm" wire:click="editCourse({{ $c->id }})">Web details</button>
                            <button class="btn btn--sm" wire:click="toggleCourse({{ $c->id }})">{{ $c->is_published ? 'Unpublish' : 'Publish' }}</button>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Branches --------------------------------------------------------- --}}
    <x-card>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2 style="margin:0 0 4px;font-size:16px;">Centres on the site</h2>
                <p class="field__hint" style="margin:0;">Full centre web details live on the <a href="{{ url('/branches') }}">Branches</a> screen.</p>
            </div>
        </div>
        @if ($branches->isNotEmpty())
            <x-data-table :head="['Centre', 'City', 'Status', '']">
                @foreach ($branches as $b)
                    <tr wire:key="wb-{{ $b->id }}">
                        <td><b>{{ $b->name }}</b><div class="field__hint">/{{ $b->slug ?: '—' }}</div></td>
                        <td>{{ $b->city ?: '—' }}</td>
                        <td>@if ($b->is_published)<x-pill variant="success">Published</x-pill>@else<x-pill variant="muted">Draft</x-pill>@endif</td>
                        <td style="text-align:right;"><button class="btn btn--sm" wire:click="toggleBranch({{ $b->id }})">{{ $b->is_published ? 'Unpublish' : 'Publish' }}</button></td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Course web-details modal ---------------------------------------- --}}
    <x-modal wire:model="showCourse" title="Programme web details" wide>
        <div class="form-grid form-grid--2">
            <x-field name="course.slug" label="URL slug" wire:model="course.slug" required />
            <x-field name="course.tagline" label="Tagline" wire:model="course.tagline" />
        </div>
        <div class="form-grid form-grid--3">
            <x-select name="course.level" label="Level" :options="collect(\App\Livewire\Website\WebsiteManager::LEVELS)->mapWithKeys(fn($l)=>[$l=>$l])->all()" placeholder="—" wire:model="course.level" />
            <x-select name="course.mode" label="Mode" :options="\App\Livewire\Website\WebsiteManager::MODES" wire:model="course.mode" />
            <x-field name="course.fee_from" label="Indicative fee (₹)" wire:model="course.fee_from" inputmode="decimal" />
        </div>
        <div class="field">
            <label class="field__label" for="c_desc">Description</label>
            <textarea id="c_desc" class="input" rows="3" wire:model="course.description"></textarea>
        </div>
        <div class="field">
            <label class="field__label" for="c_high">Highlights (one per line)</label>
            <textarea id="c_high" class="input" rows="4" wire:model="highlightsText" placeholder="Weekly mock tests&#10;Personal mentor&#10;Doubt-clearing sessions"></textarea>
        </div>
        <x-field name="course.hero_image" label="Hero image URL" wire:model="course.hero_image" />
        <div class="form-grid form-grid--2">
            <x-field name="course.seo_title" label="SEO title" wire:model="course.seo_title" />
            <x-field name="course.seo_description" label="SEO description" wire:model="course.seo_description" />
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:14px;margin-top:var(--space-2);">
            <input type="checkbox" wire:model="course.is_published"><span>Published on the website</span>
        </label>

        <x-slot:footer>
            <button class="btn" wire:click="$set('showCourse', false)">Cancel</button>
            <button class="btn btn--primary" wire:click="saveCourse">Save programme</button>
        </x-slot:footer>
    </x-modal>
</div>
