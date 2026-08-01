<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Admissions" />

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('activate')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card title="New admission">
        <form wire:submit="admit">
            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: var(--space-3);">Student</h3>
            <div class="grid-cards">
                <x-field name="s_name" label="Full name" wire:model="s_name" required />
                <x-field name="s_dob" label="Date of birth" type="date" wire:model.live="s_dob" />
                <x-field name="s_gender" label="Gender" wire:model="s_gender" />
                <x-field name="s_phone" label="Phone" wire:model="s_phone" />
                <x-field name="s_email" label="Email" type="email" wire:model="s_email" />
                <x-select name="s_branch_id" label="Branch" :options="$branches->toArray()" placeholder="Select branch" wire:model="s_branch_id" required />
            </div>

            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin: var(--space-4) 0 var(--space-3);">
                Guardian @if ($this->isMinor)<x-pill variant="warning">Required (minor)</x-pill>@endif
            </h3>
            <div class="grid-cards">
                <x-field name="g_name" label="Guardian name" wire:model="g_name" :required="$this->isMinor" />
                <x-field name="g_relation" label="Relation" wire:model="g_relation" hint="father, mother, guardian" />
                <x-field name="g_phone" label="Guardian phone" wire:model="g_phone" />
            </div>

            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin: var(--space-4) 0 var(--space-3);">Course and consent</h3>
            <div class="grid-cards">
                <x-select name="course_id" label="Course" :options="$courses->toArray()" placeholder="Select course" wire:model="course_id" required />
            </div>
            <div style="display:flex; flex-direction:column; gap: var(--space-2); margin-top: var(--space-3);">
                <label style="display:flex; align-items:center; gap: var(--space-2); min-height: var(--tap-min);">
                    <input type="checkbox" wire:model="consent_data"> Consent to store and process student data
                </label>
                <label style="display:flex; align-items:center; gap: var(--space-2); min-height: var(--tap-min);">
                    <input type="checkbox" wire:model="consent_comm"> Consent to receive communication
                </label>
            </div>

            <div style="margin-top: var(--space-4);"><x-btn type="submit" variant="primary">Admit student</x-btn></div>
        </form>
    </x-card>

    @if ($withdrawId)
        <x-card title="Withdraw enrollment">
            <form wire:submit="withdraw">
                <x-field name="withdrawReason" label="Reason" wire:model="withdrawReason" />
                <div style="display:flex; gap: var(--space-2);">
                    <x-btn type="submit" variant="primary">Withdraw</x-btn>
                    <x-btn type="button" variant="secondary" wire:click="$set('withdrawId', null)">Cancel</x-btn>
                </div>
            </form>
        </x-card>
    @endif

    @if ($provisional->isNotEmpty())
        <x-card title="Provisional (from enquiry conversions)">
            <x-data-table :head="['Name', 'Course', 'Actions']">
                @foreach ($provisional as $e)
                    <tr wire:key="prov-{{ $e->id }}">
                        <td>{{ $e->student?->name }}</td>
                        <td>{{ $e->course?->name }}</td>
                        <td>
                            <x-btn size="sm" variant="primary" wire:click="activate({{ $e->id }})">Complete admission</x-btn>
                            <x-btn size="sm" variant="secondary" wire:click="openWithdraw({{ $e->id }})">Withdraw</x-btn>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        </x-card>
    @endif

    <x-card title="Students">
        @if ($enrollments->isEmpty())
            <x-state title="No admissions yet">Admit a student above or complete a provisional conversion.</x-state>
        @else
            <x-data-table :head="['Admission #', 'Name', 'Course', 'Status', 'Actions']">
                @foreach ($enrollments as $e)
                    <tr wire:key="enr-{{ $e->id }}">
                        <td>{{ $e->student?->admission_number ?: '—' }}</td>
                        <td>{{ $e->student?->name }}</td>
                        <td>{{ $e->course?->name }}</td>
                        <td><x-pill :variant="$e->status->pillVariant()">{{ $e->status->label() }}</x-pill></td>
                        <td>
                            <x-btn size="sm" variant="secondary" wire:click="viewProfile({{ $e->student_id }})">View</x-btn>
                            @if ($e->status !== \App\Enums\EnrollmentStatus::Withdrawn)
                                <x-btn size="sm" variant="secondary" wire:click="openWithdraw({{ $e->id }})">Withdraw</x-btn>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    @if ($profile)
        <x-card title="Profile: {{ $profile->name }}">
            <div class="grid-cards">
                <div><span class="field__hint">Admission #</span><div>{{ $profile->admission_number ?: '—' }}</div></div>
                <div><span class="field__hint">Date of birth</span><div>{{ $profile->dob?->format('d-m-Y') ?: '—' }} @if($profile->isMinor())<x-pill variant="info">Minor</x-pill>@endif</div></div>
                <div><span class="field__hint">Phone</span><div>{{ $profile->phone ?: '—' }}</div></div>
            </div>
            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin: var(--space-4) 0 var(--space-2);">Guardians</h3>
            @forelse ($profile->guardians as $g)
                <div>{{ $g->name }} ({{ $g->pivot->relationship }}) @if($g->pivot->is_primary)<x-pill variant="success">Primary</x-pill>@endif</div>
            @empty
                <span class="field__hint">None linked</span>
            @endforelse
            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin: var(--space-4) 0 var(--space-2);">Enrollments</h3>
            @foreach ($profile->enrollments as $en)
                <div>{{ $en->course?->name }} — <x-pill :variant="$en->status->pillVariant()">{{ $en->status->label() }}</x-pill></div>
            @endforeach
            <div style="margin-top: var(--space-4);"><x-btn size="sm" variant="secondary" wire:click="$set('profileId', null)">Close</x-btn></div>
        </x-card>
    @endif
</div>
