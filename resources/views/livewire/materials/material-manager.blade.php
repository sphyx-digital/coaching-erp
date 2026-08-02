<div class="stack">
    <x-page-header title="Study materials">
        <x-slot:actions>
            <button class="btn btn--primary" wire:click="openCreate">Add material</button>
        </x-slot:actions>
    </x-page-header>

    @if (session('material_saved'))<div class="alert alert--success" role="status">Material saved.</div>@endif

    <x-card>
        <div class="toolbar" style="margin-bottom:var(--space-3);">
            <div class="field" style="min-width:220px;">
                <label class="field__label" for="cf">Course</label>
                <select id="cf" class="select" wire:model.live="courseFilter">
                    <option value="">All courses</option>
                    @foreach ($courses as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
        </div>

        @if ($materials->isEmpty())
            <x-state title="No materials yet">Share notes, videos and documents with students — they appear in the student portal.</x-state>
        @else
            <x-data-table :head="['Title', 'Type', 'Course / Batch', 'Status', '']">
                @foreach ($materials as $m)
                    <tr wire:key="m-{{ $m->id }}" class="is-clickable" wire:click="openEdit({{ $m->id }})" tabindex="0" wire:keydown.enter="openEdit({{ $m->id }})">
                        <td><b>{{ $m->title }}</b>@if($m->description)<div class="field__hint">{{ \Illuminate\Support\Str::limit($m->description, 70) }}</div>@endif</td>
                        <td><x-pill variant="info">{{ $m->typeLabel() }}</x-pill></td>
                        <td>{{ $m->course?->name ?? 'All' }}@if($m->batch) · {{ $m->batch->name }}@endif</td>
                        <td>@if($m->is_published)<x-pill variant="success">Published</x-pill>@else<x-pill variant="muted">Draft</x-pill>@endif</td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a class="btn btn--sm" href="{{ $m->url }}" target="_blank" rel="noopener" onclick="event.stopPropagation()">Open</a>
                            <button class="btn btn--sm" wire:click.stop="togglePublish({{ $m->id }})">{{ $m->is_published ? 'Unpublish' : 'Publish' }}</button>
                            <button class="btn btn--sm" wire:click.stop="delete({{ $m->id }})" wire:confirm="Delete this material?">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    <x-modal wire:model="showModal" title="{{ $editingId ? 'Edit material' : 'Add material' }}" wide>
        <x-field name="data.title" label="Title" wire:model="data.title" required />
        <div class="form-grid form-grid--2">
            <x-select name="data.type" label="Type" :options="\App\Livewire\Materials\MaterialManager::TYPES" wire:model="data.type" />
            <x-field name="data.url" label="Shareable link (Drive / YouTube / PDF URL)" wire:model="data.url" required />
        </div>
        <div class="form-grid form-grid--3">
            <x-select name="data.course_id" label="Course" :options="$courses->pluck('name','id')->all()" placeholder="All courses" wire:model="data.course_id" />
            <x-select name="data.batch_id" label="Batch" :options="$batches->pluck('name','id')->all()" placeholder="All batches" wire:model="data.batch_id" />
            <x-select name="data.subject_id" label="Subject" :options="$subjects->pluck('name','id')->all()" placeholder="Any subject" wire:model="data.subject_id" />
        </div>
        <div class="field">
            <label class="field__label" for="m_desc">Description</label>
            <textarea id="m_desc" class="input" rows="3" wire:model="data.description"></textarea>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:14px;"><input type="checkbox" wire:model="data.is_published"><span>Published (visible to students)</span></label>
        <x-slot:footer>
            <button class="btn" wire:click="$set('showModal', false)">Cancel</button>
            <button class="btn btn--primary" wire:click="save">Save material</button>
        </x-slot:footer>
    </x-modal>
</div>
