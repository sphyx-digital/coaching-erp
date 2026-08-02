<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Assessments" />

    @if (session('ok'))<div class="pill pill--success"><span class="pill__dot"></span> {{ session('ok') }}</div>@endif
    @error('marks')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card title="Create an assessment">
        <form wire:submit="createAssessment">
            <div class="grid-cards">
                <x-select name="batchId" label="Batch" :options="$batches->toArray()" placeholder="Select batch" wire:model="batchId" required />
                <x-field name="name" label="Name" wire:model="name" hint="Unit Test 1, Term Exam…" required />
                <x-select name="type" label="Type" :options="['test' => 'Test', 'exam' => 'Exam']" wire:model="type" />
                <x-field name="assessmentDate" label="Date" type="date" wire:model="assessmentDate" />
            </div>
            <x-btn type="submit" variant="primary">Create assessment</x-btn>
        </form>
    </x-card>

    <x-card title="Assessments">
        @if ($assessments->isEmpty())
            <x-state title="No assessments yet">Create a test or exam above.</x-state>
        @else
            <x-data-table :head="['Name', 'Batch', 'Type', 'Status', '']">
                @foreach ($assessments as $a)
                    <tr wire:key="a-{{ $a->id }}">
                        <td>{{ $a->name }}</td>
                        <td>{{ $a->batch?->name }}</td>
                        <td>{{ ucfirst($a->type) }}</td>
                        <td>
                            @if ($a->status === 'published')<x-pill variant="success">Published</x-pill>@else<x-pill variant="info">Draft</x-pill>@endif
                        </td>
                        <td><div class="row-actions"><x-btn size="sm" variant="secondary" wire:click="select({{ $a->id }})">Open</x-btn></div></td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    @if ($assessment)
        <x-card title="{{ $assessment->name }} — subjects & marks">
            <form wire:submit="addSubject" style="margin-bottom: var(--space-4);">
                <div class="grid-cards">
                    <x-select name="subjectId" label="Add subject" :options="$subjectOptions->toArray()" placeholder="Select subject" wire:model="subjectId" />
                    <x-field name="maxMarks" label="Max marks" type="number" wire:model="maxMarks" />
                </div>
                <x-btn type="submit" variant="secondary" size="sm">Add subject</x-btn>
            </form>

            @if ($assessment->subjects->isEmpty())
                <x-state title="No subjects">Add subjects to enter marks.</x-state>
            @elseif ($students->isEmpty())
                <x-state title="No students">Assign students to the batch first.</x-state>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                @foreach ($assessment->subjects as $as)
                                    <th class="num">{{ $as->subject?->name }}<br><span class="field__hint">/ {{ (int) $as->max_marks }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($students as $student)
                            <tr wire:key="row-{{ $student->id }}">
                                <td>{{ $student->name }}</td>
                                @foreach ($assessment->subjects as $as)
                                    <td class="num">
                                        <input type="text" class="input" style="max-width:80px; text-align:right;"
                                               wire:model="marks.{{ $as->id }}.{{ $student->id }}"
                                               placeholder="—" title="Enter marks or A for absent">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="field__hint" style="margin-top: var(--space-2);">Enter marks, or "A" for absent. A blank cell means not entered (not zero).</p>

                <div style="display:flex; gap: var(--space-2); margin-top: var(--space-4); flex-wrap:wrap;">
                    <x-btn variant="primary" wire:click="saveMarks">Save marks</x-btn>
                    @if ($assessment->status !== 'published')
                        <x-btn variant="secondary" wire:click="publish">Publish</x-btn>
                    @endif
                    <x-btn variant="secondary" wire:click="generateCards">Generate report cards</x-btn>
                </div>
            @endif
        </x-card>

        @if ($performance && $performance['count'] > 0)
            <x-card title="Performance">
                <div class="grid-cards">
                    <x-kpi label="Average" :value="number_format($performance['average_bp'] / 100, 1).'%'" />
                    <x-kpi label="Passed" :value="$performance['passed'].' / '.$performance['count']" />
                </div>
                <h3 style="font-size: var(--text-sm); color: var(--text-muted); margin: var(--space-4) 0 var(--space-2);">Toppers</h3>
                @foreach ($performance['toppers'] as $t)
                    @php($st = $students->firstWhere('id', $t['student_id']))
                    <div>{{ $loop->iteration }}. {{ $st?->name }} — <strong>{{ number_format($t['percent_bp'] / 100, 1) }}%</strong> ({{ $t['grade'] }})
                        <a href="{{ url('/report-cards/'.$assessment->id.'/'.$t['student_id']) }}" target="_blank" style="font-size: var(--text-xs);">report card</a>
                    </div>
                @endforeach
            </x-card>
        @endif
    @endif
</div>
