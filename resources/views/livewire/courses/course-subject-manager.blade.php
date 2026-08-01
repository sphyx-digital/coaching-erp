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
            <x-data-table :head="['Name', 'Code', 'Duration', ['label' => 'Subjects', 'num' => true]]">
                @foreach ($courses as $course)
                    <tr wire:key="course-{{ $course->id }}">
                        <td>{{ $course->name }}</td>
                        <td>{{ $course->code }}</td>
                        <td>{{ $course->duration_months ? $course->duration_months.' mo' : '—' }}</td>
                        <td class="num">{{ $course->subjects_count }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

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
