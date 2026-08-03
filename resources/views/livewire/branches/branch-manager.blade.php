<div class="stack">
    <div class="page-header">
        <h1 class="page-header__title">Branches</h1>
        <x-btn variant="primary" wire:click="openCreate"><x-icon name="branch" /> Add branch</x-btn>
    </div>

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif

    <x-card>
        <div class="toolbar">
            <input class="input" type="search" placeholder="Search name, code, city…" wire:model.live.debounce.300ms="search">
            @foreach (['published' => 'Published', 'draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
                <button type="button" class="chip" wire:click="togglePublishFilter('{{ $key }}')" aria-pressed="{{ in_array($key, $publishFilter) ? 'true' : 'false' }}">
                    @if (in_array($key, $publishFilter))<span class="chip__check">✓</span>@endif {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="table-wrap">
            <table class="table table--dense">
                <thead>
                    <tr>
                        <x-th field="name" :sort="$sortField" :dir="$sortDir">Branch</x-th>
                        <x-th field="code" :sort="$sortField" :dir="$sortDir">Code</x-th>
                        <th>Location</th>
                        <th>Manager</th>
                        <th>Website</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($branches as $b)
                    <tr wire:key="branch-{{ $b->id }}" class="is-clickable" wire:click="openEdit({{ $b->id }})" tabindex="0" wire:keydown.enter="openEdit({{ $b->id }})">
                        <td><strong>{{ $b->name }}</strong>@if ($b->tagline)<br><span class="field__hint">{{ $b->tagline }}</span>@endif</td>
                        <td>{{ $b->code }}</td>
                        <td>{{ $b->shortAddress() ?: '—' }}</td>
                        <td>{{ $b->manager_name ?: '—' }}</td>
                        <td>@if ($b->is_published)<x-pill variant="success">Published</x-pill>@else<x-pill variant="info">Draft</x-pill>@endif</td>
                        <td>@if ($b->is_active)<x-pill variant="success">Active</x-pill>@else<x-pill variant="warning">Inactive</x-pill>@endif</td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="btn btn--sm btn--secondary" wire:click.stop="toggleActive({{ $b->id }})">{{ $b->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-state title="No branches">Add your first branch.</x-state></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Modal-first CRUD (server-side via Livewire) --}}
    <x-modal wire:model="showModal" :title="$editingId ? 'Edit branch' : 'Add branch'" wide>
        <div class="form-section">
            <div class="form-section__title">Identity</div>
            <div class="form-grid">
                <x-field label="Branch name" name="data.name" wire:model="data.name" required />
                <x-field label="Code" name="data.code" wire:model="data.code" required hint="Short unique code" />
                <x-select label="Type" name="data.branch_type" :options="$branchTypes" wire:model="data.branch_type" />
                <x-field label="Legal name" name="data.legal_name" wire:model="data.legal_name" />
                <x-field label="Established on" type="date" name="data.established_on" wire:model="data.established_on" />
                <x-field label="Student capacity" type="number" name="data.student_capacity" wire:model="data.student_capacity" />
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Contact</div>
            <div class="form-grid">
                <x-field label="Phone" name="data.phone" wire:model="data.phone" numeric maxlength="15" />
                <x-field label="Alternate phone" name="data.alt_phone" wire:model="data.alt_phone" numeric maxlength="15" />
                <x-field label="WhatsApp" name="data.whatsapp" wire:model="data.whatsapp" numeric maxlength="15" />
                <x-field label="Email" type="email" name="data.email" wire:model="data.email" />
                <x-field label="Support email" type="email" name="data.support_email" wire:model="data.support_email" />
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Address & location</div>
            <div class="form-grid">
                <x-field label="Address line 1" name="data.address" wire:model="data.address" />
                <x-field label="Address line 2" name="data.address_line2" wire:model="data.address_line2" />
                <x-field label="Landmark" name="data.landmark" wire:model="data.landmark" />
                <x-field label="Locality" name="data.locality" wire:model="data.locality" />
                <x-field label="City" name="data.city" wire:model="data.city" />
                <x-combobox label="State" :options="$states" wire:model="data.state" placeholder="Select state" />
                <x-field label="Pincode" name="data.pincode" wire:model="data.pincode" numeric maxlength="6" />
                <x-field label="Country" name="data.country" wire:model="data.country" />
                <x-field label="Latitude" name="data.latitude" wire:model="data.latitude" />
                <x-field label="Longitude" name="data.longitude" wire:model="data.longitude" />
                <x-field label="Google Maps URL" name="data.google_maps_url" wire:model="data.google_maps_url" />
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Management & legal</div>
            <div class="form-grid">
                <x-field label="Manager name" name="data.manager_name" wire:model="data.manager_name" />
                <x-field label="Manager phone" name="data.manager_phone" wire:model="data.manager_phone" numeric maxlength="15" />
                <x-field label="Manager email" type="email" name="data.manager_email" wire:model="data.manager_email" />
                <x-field label="GSTIN" name="data.gstin" wire:model="data.gstin" />
                <x-field label="PAN" name="data.pan" wire:model="data.pan" />
                <x-field label="Registration no." name="data.registration_number" wire:model="data.registration_number" />
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Website & display</div>
            <div class="form-grid">
                <x-field label="Tagline" name="data.tagline" wire:model="data.tagline" />
                <x-field label="Hero image URL" name="data.hero_image" wire:model="data.hero_image" />
                <x-field label="Thumbnail URL" name="data.thumbnail" wire:model="data.thumbnail" />
                <x-field label="Display order" type="number" name="data.display_order" wire:model="data.display_order" />
            </div>
            <label class="field"><span class="field__label">Short description</span>
                <textarea class="textarea" wire:model="data.description"></textarea></label>
            <label class="field"><span class="field__label">About (long)</span>
                <textarea class="textarea" wire:model="data.about" style="min-height:120px;"></textarea></label>

            <span class="field__label">Amenities</span>
            <div style="display:flex; flex-wrap:wrap; gap: var(--space-2); margin-bottom: var(--space-3);">
                @foreach ($amenityOptions as $a)
                    <label class="chip" style="cursor:pointer;">
                        <input type="checkbox" wire:model="data.amenities" value="{{ $a }}" style="margin-right:4px;"> {{ $a }}
                    </label>
                @endforeach
            </div>

            <div class="form-grid">
                <x-field label="Facebook" name="data.social_facebook" wire:model="data.social_facebook" />
                <x-field label="Instagram" name="data.social_instagram" wire:model="data.social_instagram" />
                <x-field label="YouTube" name="data.social_youtube" wire:model="data.social_youtube" />
                <x-field label="Website" name="data.social_website" wire:model="data.social_website" />
                <x-field label="SEO title" name="data.seo_title" wire:model="data.seo_title" />
                <x-field label="SEO keywords" name="data.seo_keywords" wire:model="data.seo_keywords" />
            </div>
            <label class="field"><span class="field__label">SEO description</span>
                <textarea class="textarea" wire:model="data.seo_description"></textarea></label>
        </div>

        <div class="form-section">
            <div class="form-section__title">Status</div>
            <label style="display:flex; align-items:center; gap: var(--space-2); min-height: var(--tap-min);">
                <input type="checkbox" wire:model="data.is_active"> Active (operating)
            </label>
            <label style="display:flex; align-items:center; gap: var(--space-2); min-height: var(--tap-min);">
                <input type="checkbox" wire:model="data.is_published"> Published on public website
            </label>
        </div>

        <x-slot:footer>
            <x-btn variant="secondary" x-on:click="open = false">Cancel</x-btn>
            <x-btn variant="primary" wire:click="save">{{ $editingId ? 'Update branch' : 'Create branch' }}</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
