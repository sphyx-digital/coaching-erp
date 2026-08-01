<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Enquiries" />

    @if (session('ok'))
        <div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>
    @endif
    @error('convert')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    {{-- Counsellor dashboard --}}
    <div class="grid-cards">
        <x-kpi label="Open enquiries" :value="$kpiOpen" />
        <x-kpi label="Due follow-ups today" :value="$kpiDue" />
        <x-kpi label="Converted this session" :value="$kpiConverted" />
    </div>

    <div class="stack" style="grid-template-columns: 1fr; gap: var(--space-5);">
        {{-- Capture --}}
        <x-card title="Capture an enquiry">
            @if ($this->isDuplicate)
                <div class="pill pill--warning" style="margin-bottom: var(--space-3);"><span class="pill__dot"></span> A similar enquiry (same phone and course) already exists this session. You can still save.</div>
            @endif
            <form wire:submit="create">
                <div class="grid-cards">
                    <x-field name="name" label="Student name" wire:model="name" required />
                    <x-field name="phone" label="Phone" wire:model.blur="phone" />
                    <x-field name="email" label="Email" type="email" wire:model="email" />
                    <x-field name="guardian_name" label="Guardian name" wire:model="guardian_name" />
                    <x-field name="guardian_phone" label="Guardian phone" wire:model="guardian_phone" />
                    <x-field name="source" label="Source" wire:model="source" hint="walk-in, referral, online…" />
                    <x-select name="branch_id" label="Branch" :options="$branches->toArray()" placeholder="Select branch" wire:model="branch_id" required />
                    <x-select name="course_id" label="Interested course" :options="$courses->toArray()" placeholder="Not sure yet" wire:model.live="course_id" />
                </div>
                <x-btn type="submit" variant="primary">Add enquiry</x-btn>
            </form>
        </x-card>

        {{-- Action panel --}}
        @if ($panelId)
            <x-card title="{{ $panelMode === 'lost' ? 'Mark as lost' : 'Log a follow-up' }}">
                <form wire:submit="savePanel">
                    <label class="field">
                        <span class="field__label">{{ $panelMode === 'lost' ? 'Reason' : 'Note' }}</span>
                        <textarea class="textarea" wire:model="panelText"></textarea>
                    </label>
                    @if ($panelMode !== 'lost')
                        <x-field name="panelFollowUp" label="Next follow-up on" type="date" wire:model="panelFollowUp" />
                    @endif
                    <div style="display:flex; gap: var(--space-2);">
                        <x-btn type="submit" variant="primary">Save</x-btn>
                        <x-btn type="button" variant="secondary" wire:click="closePanel">Cancel</x-btn>
                    </div>
                </form>
            </x-card>
        @endif

        {{-- Due today --}}
        <x-card title="Due follow-ups today">
            @if ($dueToday->isEmpty())
                <x-state title="Nothing due">No follow-ups are due today.</x-state>
            @else
                <x-data-table :head="['Enquiry', 'Name', 'Course', 'Due']">
                    @foreach ($dueToday as $e)
                        <tr wire:key="due-{{ $e->id }}">
                            <td>{{ $e->enquiry_number }}</td>
                            <td>{{ $e->name }}</td>
                            <td>{{ $e->course?->name ?: '—' }}</td>
                            <td>{{ $e->next_follow_up_on?->format('d-m-Y') }}</td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- Pipeline --}}
        <x-card title="Pipeline">
            <div class="toolbar">
                <input class="input" type="search" placeholder="Search name, phone, number…" wire:model.live.debounce.300ms="search">
                <button type="button" class="chip" wire:click="$set('statusFilter', '')" aria-pressed="{{ $statusFilter === '' ? 'true' : 'false' }}">All</button>
                @foreach (\App\Enums\EnquiryStatus::cases() as $s)
                    <button type="button" class="chip" wire:click="$set('statusFilter', '{{ $s->value }}')" aria-pressed="{{ $statusFilter === $s->value ? 'true' : 'false' }}">{{ $s->label() }}</button>
                @endforeach
            </div>

            <div class="table-wrap">
                <table class="table table--dense">
                    <thead>
                        <tr>
                            <x-th field="enquiry_number" :sort="$sortField" :dir="$sortDir">Enquiry</x-th>
                            <x-th field="name" :sort="$sortField" :dir="$sortDir">Name</x-th>
                            <th>Course</th>
                            <x-th field="status" :sort="$sortField" :dir="$sortDir">Status</x-th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($enquiries as $e)
                        <tr wire:key="enq-{{ $e->id }}">
                            <td>{{ $e->enquiry_number }}</td>
                            <td>{{ $e->name }}<br><span class="field__hint">{{ $e->phone }}</span></td>
                            <td>{{ $e->course?->name ?: '—' }}</td>
                            <td><x-pill :variant="$e->status->pillVariant()">{{ $e->status->label() }}</x-pill></td>
                            <td>
                                <div style="display:flex; gap: var(--space-1); flex-wrap: wrap;">
                                    @if (! $e->status->isTerminal() && $e->status !== \App\Enums\EnquiryStatus::Lost)
                                        @foreach ($statuses as $s)
                                            @if ($e->status !== $s && $e->status->canTransitionTo($s))
                                                <x-btn size="sm" variant="secondary" wire:click="setStatus({{ $e->id }}, '{{ $s->value }}')">{{ $s->label() }}</x-btn>
                                            @endif
                                        @endforeach
                                        <x-btn size="sm" variant="secondary" wire:click="openPanel({{ $e->id }}, 'note')">Note</x-btn>
                                        <x-btn size="sm" variant="primary" wire:click="convert({{ $e->id }})">Convert</x-btn>
                                        <x-btn size="sm" variant="secondary" wire:click="openPanel({{ $e->id }}, 'lost')">Lost</x-btn>
                                    @elseif ($e->status === \App\Enums\EnquiryStatus::Lost)
                                        <x-btn size="sm" variant="secondary" wire:click="setStatus({{ $e->id }}, 'contacted')">Reopen</x-btn>
                                    @else
                                        <span class="field__hint">Admission drafted</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-state title="No enquiries found">Try a different search or filter.</x-state></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
