<div class="container-narrow stack" style="gap: var(--space-5);">
    <x-page-header title="Courses and subjects" />

    <div class="grid-cards" style="grid-template-columns: 1fr 1fr;">
        <x-card title="Add a course">
            <form wire:submit="addCourse">
                <x-field name="courseName" label="Name" wire:model="courseName" required />
                <x-field name="courseCode" label="Code" wire:model="courseCode" required />
                <x-field name="courseDuration" label="Duration (months)" type="number" wire:model="courseDuration" />
                <x-btn type="submit" variant="primary">Add course</x-btn>
            </form>
        </x-card>

        <x-card title="Add a subject">
            <form wire:submit="addSubject">
                <x-field name="subjectName" label="Name" wire:model="subjectName" required />
                <x-field name="subjectCode" label="Code" wire:model="subjectCode" required />
                <x-select name="subjectCourseId" label="Course" :options="$courseOptions->toArray()" placeholder="Shared / none" wire:model="subjectCourseId" />
                <x-btn type="submit" variant="primary">Add subject</x-btn>
            </form>
        </x-card>
    </div>

    <x-card title="Courses">
        @if ($courses->isEmpty())
            <x-state title="No courses yet">Add a course to start enrolling students.</x-state>
        @else
            <x-data-table :head="['Name', 'Code', 'Duration', ['label' => 'Subjects', 'num' => true], '']">
                @foreach ($courses as $course)
                    <tr wire:key="course-{{ $course->id }}" class="is-clickable" wire:click="view({{ $course->id }})" tabindex="0" wire:keydown.enter="view({{ $course->id }})">
                        <td>{{ $course->name }}</td>
                        <td>{{ $course->code }}</td>
                        <td>{{ $course->duration_months ? $course->duration_months.' mo' : '—' }}</td>
                        <td class="num">{{ $course->subjects_count }}</td>
                        <td class="row-chevron" style="text-align:right;">&rsaquo;</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Course detail drawer --}}
    <x-drawer wire:model="viewing" :title="$record?->name" eyebrow="Course" :subtitle="$record?->code">
        @if ($record)
            <dl class="detail-list">
                <dt>Code</dt><dd>{{ $record->code }}</dd>
                <dt>Duration</dt><dd>{{ $record->duration_months ? $record->duration_months.' months' : '—' }}</dd>
                <dt>Level</dt><dd>{{ $record->level ?: '—' }}</dd>
                <dt>Website</dt><dd>@if($record->is_published)<x-pill variant="success">Published</x-pill>@else<x-pill variant="muted">Not published</x-pill>@endif</dd>
                <dt>Status</dt><dd>@if($record->is_active)<x-pill variant="success">Active</x-pill>@else<x-pill variant="muted">Inactive</x-pill>@endif</dd>
            </dl>
            @if ($record->description)
                <div class="detail-section">
                    <div class="detail-section__title">Description</div>
                    <p style="font-size: var(--text-sm); margin:0;">{{ $record->description }}</p>
                </div>
            @endif
            <div class="detail-section">
                <div class="detail-section__title">Subjects ({{ $record->subjects_count }})</div>
                @forelse ($record->subjects as $s)
                    <div style="padding:5px 0; border-bottom:1px solid var(--border);">{{ $s->name }} <span class="field__hint">{{ $s->code }}</span></div>
                @empty
                    <span class="field__hint">No subjects linked yet.</span>
                @endforelse
            </div>
        @endif
        <x-slot:footer>
            <a class="btn btn--sm btn--secondary" href="{{ url('/website') }}">Website details</a>
            <a class="btn btn--sm btn--secondary" href="{{ url('/fees/setup') }}">Fee setup</a>
        </x-slot:footer>
    </x-drawer>

    <x-card title="Subjects">
        @if ($subjects->isEmpty())
            <x-state title="No subjects yet">Add subjects to build assessments and timetables.</x-state>
        @else
            <x-data-table :head="['Name', 'Code', 'Course']">
                @foreach ($subjects as $subject)
                    <tr wire:key="subject-{{ $subject->id }}">
                        <td>{{ $subject->name }}</td>
                        <td>{{ $subject->code }}</td>
                        <td>{{ $subject->course?->name ?: 'Shared' }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
