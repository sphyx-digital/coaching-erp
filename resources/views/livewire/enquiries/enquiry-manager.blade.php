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
                    <x-field name="phone" label="Phone" wire:model.blur="phone" numeric maxlength="15" />
                    <x-field name="email" label="Email" type="email" wire:model="email" />
                    <x-field name="guardian_name" label="Guardian name" wire:model="guardian_name" />
                    <x-field name="guardian_phone" label="Guardian phone" wire:model="guardian_phone" numeric maxlength="15" />
                    <x-field name="source" label="Source" wire:model="source" hint="walk-in, referral, online…" />
                    <x-select name="branch_id" label="Branch" :options="$branches->toArray()" placeholder="Select branch" wire:model="branch_id" required />
                    <x-select name="course_id" label="Interested course" :options="$courses->toArray()" placeholder="Not sure yet" wire:model.live="course_id" />
                </div>
                <x-btn type="submit" variant="primary">Add enquiry</x-btn>
            </form>
        </x-card>

        {{-- Due today --}}
        <x-card title="Due follow-ups today">
            @if ($dueToday->isEmpty())
                <x-state title="Nothing due">No follow-ups are due today.</x-state>
            @else
                <x-data-table :head="['Enquiry', 'Name', 'Course', 'Due', '']">
                    @foreach ($dueToday as $e)
                        <tr wire:key="due-{{ $e->id }}" class="is-clickable" wire:click="view({{ $e->id }})" tabindex="0" wire:keydown.enter="view({{ $e->id }})">
                            <td>{{ $e->enquiry_number }}</td>
                            <td>{{ $e->name }}</td>
                            <td>{{ $e->course?->name ?: '—' }}</td>
                            <td>{{ $e->next_follow_up_on?->format('d-m-Y') }}</td>
                            <td class="row-chevron" style="text-align:right;">&rsaquo;</td>
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

            {{-- Bulk actions bar --}}
            @if ($this->selectedCount())
                <div class="bulkbar">
                    <span class="bulkbar__count">{{ $this->selectedCount() }} selected</span>
                    <x-btn size="sm" variant="secondary" wire:click="bulkStatus('contacted')">Mark contacted</x-btn>
                    <x-btn size="sm" variant="secondary" wire:click="bulkStatus('follow_up')">Mark follow-up</x-btn>
                    <x-btn size="sm" variant="secondary" wire:click="bulkStatus('lost')">Mark lost</x-btn>
                    <span class="bulkbar__spacer"></span>
                    <x-btn size="sm" variant="secondary" wire:click="clearSelection">Clear</x-btn>
                </div>
            @endif

            <div class="table-wrap">
                <table class="table table--dense">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" aria-label="Select all"
                                @change="$event.target.checked ? $wire.selectAllVisible() : $wire.clearSelection()"
                                @checked($this->selectedCount() && $this->selectedCount() === count($enquiries))></th>
                            <x-th field="enquiry_number" :sort="$sortField" :dir="$sortDir">Enquiry</x-th>
                            <x-th field="name" :sort="$sortField" :dir="$sortDir">Name</x-th>
                            <th>Course</th>
                            <th>Branch</th>
                            <th>Follow-up</th>
                            <x-th field="status" :sort="$sortField" :dir="$sortDir">Status</x-th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($enquiries as $e)
                        <tr wire:key="enq-{{ $e->id }}">
                            <td class="col-check"><input type="checkbox" value="{{ $e->id }}" wire:model.live="selected" aria-label="Select {{ $e->name }}"></td>
                            <td>{{ $e->enquiry_number }}</td>
                            <td>{{ $e->name }}<br><span class="field__hint">{{ $e->phone ?: '—' }}</span></td>
                            <td>{{ $e->course?->name ?: '—' }}</td>
                            <td>{{ $e->branch?->name ?: '—' }}</td>
                            <td>{{ $e->next_follow_up_on?->format('d-m-Y') ?: '—' }}</td>
                            <td><x-pill :variant="$e->status->pillVariant()">{{ $e->status->label() }}</x-pill></td>
                            <td>
                                <div class="row-actions">
                                    <x-btn size="sm" variant="secondary" wire:click="view({{ $e->id }})">View</x-btn>
                                    @if (! $e->status->isTerminal() && $e->status !== \App\Enums\EnquiryStatus::Lost)
                                        <x-btn size="sm" variant="primary" wire:click="convert({{ $e->id }})">Convert</x-btn>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-state title="No enquiries found">Try a different search or filter.</x-state></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Detail drawer: full enquiry + activity + all actions --}}
    <x-drawer wire:model="viewing" :title="$record?->name" :eyebrow="$record?->enquiry_number"
              :subtitle="$record?->status?->label()">
        @if ($record)
            <dl class="detail-list">
                <dt>Phone</dt><dd>{{ $record->phone ?: '—' }}</dd>
                <dt>Email</dt><dd>{{ $record->email ?: '—' }}</dd>
                <dt>Guardian</dt><dd>{{ $record->guardian_name ?: '—' }}@if($record->guardian_phone) · {{ $record->guardian_phone }}@endif</dd>
                <dt>Course</dt><dd>{{ $record->course?->name ?: 'Not sure yet' }}</dd>
                <dt>Branch</dt><dd>{{ $record->branch?->name ?: '—' }}</dd>
                <dt>Source</dt><dd>{{ $record->source ?: '—' }}</dd>
                <dt>Counsellor</dt><dd>{{ $record->counsellor?->name ?: '—' }}</dd>
                <dt>Next follow-up</dt><dd>{{ $record->next_follow_up_on?->format('d-m-Y') ?: '—' }}</dd>
                @if ($record->convertedStudent)
                    <dt>Admission</dt><dd><a href="{{ url('/admissions') }}">{{ $record->convertedStudent->name }}</a></dd>
                @endif
                @if ($record->lost_reason)
                    <dt>Lost reason</dt><dd>{{ $record->lost_reason }}</dd>
                @endif
            </dl>

            {{-- Inline note / lost form --}}
            @if ($panelMode)
                <div class="detail-section">
                    <div class="detail-section__title">{{ $panelMode === 'lost' ? 'Mark as lost' : 'Log a follow-up' }}</div>
                    <label class="field">
                        <span class="field__label">{{ $panelMode === 'lost' ? 'Reason' : 'Note' }}</span>
                        <textarea class="textarea" wire:model="panelText"></textarea>
                    </label>
                    @if ($panelMode !== 'lost')
                        <x-field name="panelFollowUp" label="Next follow-up on" type="date" wire:model="panelFollowUp" />
                    @endif
                    <div style="display:flex; gap: var(--space-2); margin-top: var(--space-2);">
                        <x-btn variant="primary" wire:click="savePanel">Save</x-btn>
                        <x-btn variant="secondary" wire:click="closePanel">Cancel</x-btn>
                    </div>
                </div>
            @endif

            {{-- Activity timeline --}}
            <div class="detail-section">
                <div class="detail-section__title">Activity</div>
                @if ($record->activities->isEmpty())
                    <p class="field__hint">No activity logged yet.</p>
                @else
                    <ul class="timeline">
                        @foreach ($record->activities as $a)
                            <li wire:key="act-{{ $a->id }}">
                                <div class="t-when">{{ $a->created_at?->format('d-m-Y H:i') }}</div>
                                <div>
                                    @if ($a->type === 'status_change')
                                        Status changed{{ $a->to_status ? ' to '.\App\Enums\EnquiryStatus::from($a->to_status)->label() : '' }}
                                    @else
                                        {{ ucfirst($a->type ?: 'note') }}
                                    @endif
                                    @if ($a->notes) — {{ $a->notes }}@endif
                                    @if ($a->next_follow_up_on) <span class="field__hint">(next: {{ $a->next_follow_up_on->format('d-m-Y') }})</span>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <x-slot:footer>
            @if ($record && ! $record->status->isTerminal() && $record->status !== \App\Enums\EnquiryStatus::Lost)
                @foreach ($statuses as $s)
                    @if ($record->status !== $s && $record->status->canTransitionTo($s))
                        <x-btn size="sm" variant="secondary" wire:click="setStatus({{ $record->id }}, '{{ $s->value }}')">{{ $s->label() }}</x-btn>
                    @endif
                @endforeach
                <x-btn size="sm" variant="secondary" wire:click="openPanel('note')">Note</x-btn>
                <x-btn size="sm" variant="primary" wire:click="convert({{ $record->id }})">Convert</x-btn>
                <x-btn size="sm" variant="secondary" wire:click="openPanel('lost')">Lost</x-btn>
            @elseif ($record && $record->status === \App\Enums\EnquiryStatus::Lost)
                <x-btn size="sm" variant="secondary" wire:click="setStatus({{ $record->id }}, 'contacted')">Reopen</x-btn>
            @elseif ($record)
                <span class="field__hint">Admission drafted from this enquiry.</span>
            @endif
        </x-slot:footer>
    </x-drawer>
</div>
