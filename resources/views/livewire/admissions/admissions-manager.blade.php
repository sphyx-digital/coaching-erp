<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Admissions" />

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('activate')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card title="New admission">
        <form wire:submit="admit">
            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: var(--space-3);">Student</h3>
            <x-name-fields field="s" label="Student name" :required="true" />
            <div class="grid-cards">
                <x-field name="s_dob" label="Date of birth" type="date" wire:model.live="s_dob" />
                <x-field name="s_gender" label="Gender" wire:model="s_gender" />
                <x-phone-field field="s_phone" dial-field="s_dial" label="Phone" />
                <x-field name="s_email" label="Email" type="email" wire:model="s_email" />
                <x-select name="s_branch_id" label="Branch" :options="$branches->toArray()" placeholder="Select branch" wire:model="s_branch_id" required />
            </div>

            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin: var(--space-4) 0 var(--space-3);">
                Guardian @if ($this->isMinor)<x-pill variant="warning">Required (minor)</x-pill>@endif
            </h3>
            <x-name-fields field="g" label="Guardian name" />
            <div class="grid-cards">
                <x-field name="g_relation" label="Relation" wire:model="g_relation" hint="father, mother, guardian" />
                <x-phone-field field="g_phone" dial-field="g_dial" label="Guardian phone" />
            </div>

            <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin: var(--space-4) 0 var(--space-3);">Course and consent</h3>
            <div class="grid-cards">
                <x-combobox label="Course" :options="$courses->toArray()" wire:model="course_id" placeholder="Search course…" />
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

    @if ($provisional->isNotEmpty())
        <x-card title="Provisional (from enquiry conversions)">
            <x-data-table :head="['Name', 'Course', 'Actions']">
                @foreach ($provisional as $e)
                    <tr wire:key="prov-{{ $e->id }}" class="is-clickable" wire:click="viewProfile({{ $e->student_id }})" tabindex="0" wire:keydown.enter="viewProfile({{ $e->student_id }})">
                        <td>{{ $e->student?->name }}</td>
                        <td>{{ $e->course?->name }}</td>
                        <td>
                            <div class="row-actions">
                                <x-btn size="sm" variant="primary" wire:click.stop="activate({{ $e->id }})">Complete</x-btn>
                            </div>
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
                    <tr wire:key="enr-{{ $e->id }}" class="is-clickable" wire:click="viewProfile({{ $e->student_id }})" tabindex="0" wire:keydown.enter="viewProfile({{ $e->student_id }})">
                        <td>{{ $e->student?->admission_number ?: '—' }}</td>
                        <td>{{ $e->student?->name }}</td>
                        <td>{{ $e->course?->name }}</td>
                        <td><x-pill :variant="$e->status->pillVariant()">{{ $e->status->label() }}</x-pill></td>
                        <td>
                            <div class="row-actions">
                                @if ($e->status !== \App\Enums\EnrollmentStatus::Withdrawn)
                                    <x-btn size="sm" variant="secondary" wire:click.stop="openWithdraw({{ $e->id }})">Withdraw</x-btn>
                                @else
                                    <span class="field__hint">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Student detail drawer --}}
    <x-drawer wire:model="viewing" :title="$profile?->name" eyebrow="Student"
              :subtitle="$profile?->admission_number ? 'Admission '.$profile->admission_number : 'No admission number yet'">
        @if ($profile)
            <dl class="detail-list">
                <dt>Admission #</dt><dd>{{ $profile->admission_number ?: '—' }}</dd>
                <dt>Date of birth</dt><dd>{{ $profile->dob?->format('d-m-Y') ?: '—' }} @if($profile->isMinor())<x-pill variant="info">Minor</x-pill>@endif</dd>
                <dt>Gender</dt><dd>{{ $profile->gender ?: '—' }}</dd>
                <dt>Phone</dt><dd>{{ $profile->phone ?: '—' }}</dd>
                <dt>Email</dt><dd>{{ $profile->email ?: '—' }}</dd>
                <dt>Branch</dt><dd>{{ $profile->branch?->name ?? '—' }}</dd>
            </dl>

            <div class="detail-section">
                <div class="detail-section__title">Guardians</div>
                @forelse ($profile->guardians as $g)
                    <div style="margin-bottom:4px;">{{ $g->name }} <span class="field__hint">({{ $g->pivot->relationship }})</span> @if($g->pivot->is_primary)<x-pill variant="success">Primary</x-pill>@endif</div>
                @empty
                    <span class="field__hint">None linked</span>
                @endforelse
            </div>

            <div class="detail-section">
                <div class="detail-section__title">Enrollments</div>
                @foreach ($profile->enrollments as $en)
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:var(--space-2); padding:6px 0; border-bottom:1px solid var(--border);">
                        <div>{{ $en->course?->name }} <x-pill :variant="$en->status->pillVariant()">{{ $en->status->label() }}</x-pill></div>
                        <div style="display:flex; gap:4px; white-space:nowrap;">
                            @if ($en->status === \App\Enums\EnrollmentStatus::Provisional)
                                <x-btn size="sm" variant="primary" wire:click="activate({{ $en->id }})">Complete</x-btn>
                            @endif
                            @if ($en->status !== \App\Enums\EnrollmentStatus::Withdrawn)
                                <x-btn size="sm" variant="secondary" wire:click="openWithdraw({{ $en->id }})">Withdraw</x-btn>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($withdrawId)
                <div class="detail-section">
                    <div class="detail-section__title">Withdraw enrollment</div>
                    <x-field name="withdrawReason" label="Reason" wire:model="withdrawReason" />
                    <div style="display:flex; gap: var(--space-2); margin-top: var(--space-2);">
                        <x-btn variant="primary" wire:click="withdraw">Confirm withdraw</x-btn>
                        <x-btn variant="secondary" wire:click="$set('withdrawId', null)">Cancel</x-btn>
                    </div>
                </div>
            @endif
        @endif

        <x-slot:footer>
            <a class="btn btn--sm btn--secondary" href="{{ url('/id-cards') }}">ID card</a>
            <a class="btn btn--sm btn--secondary" href="{{ url('/fees') }}">Fees</a>
        </x-slot:footer>
    </x-drawer>
</div>
