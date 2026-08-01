<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Batches" />

    @error('assign')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror
    @error('move')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror
    @error('delete')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card title="Create a batch">
        <form wire:submit="create">
            <div class="grid-cards">
                <x-field name="name" label="Name" wire:model="name" required />
                <x-field name="code" label="Code" wire:model="code" required />
                <x-field name="capacity" label="Capacity" type="number" wire:model="capacity" hint="0 = unlimited" />
                <x-select name="course_id" label="Course" :options="$courses->toArray()" placeholder="Select course" wire:model="course_id" required />
                <x-select name="teacher_id" label="Teacher" :options="$teachers->toArray()" placeholder="Unassigned" wire:model="teacher_id" />
                <x-select name="classroom_id" label="Room" :options="$rooms->toArray()" placeholder="Unassigned" wire:model="classroom_id" />
            </div>
            <x-btn type="submit" variant="primary">Create batch</x-btn>
        </form>
    </x-card>

    <x-card title="Batches">
        @if ($batches->isEmpty())
            <x-state title="No batches yet">Create a batch to assign students and build a timetable.</x-state>
        @else
            <x-data-table :head="['Batch', 'Course', 'Teacher', ['label' => 'Filled', 'num' => true], 'Students', 'Actions']">
                @foreach ($batches as $batch)
                    <tr wire:key="batch-{{ $batch->id }}">
                        <td>{{ $batch->name }}</td>
                        <td>{{ $batch->course?->name }}</td>
                        <td>{{ $batch->teacher?->name ?: '—' }}</td>
                        <td class="num">{{ $batch->live_count }}{{ $batch->capacity ? ' / '.$batch->capacity : '' }}</td>
                        <td>
                            @foreach (($assignedByBatch[$batch->id] ?? []) as $en)
                                <div style="display:flex; align-items:center; gap: var(--space-2);">
                                    <span>{{ $en->student?->name }}</span>
                                    <a href="#" wire:click.prevent="$set('moveId', {{ $en->id }})" style="font-size: var(--text-xs);">move</a>
                                </div>
                            @endforeach
                            @if (empty($assignedByBatch[$batch->id]))<span class="field__hint">No students</span>@endif
                        </td>
                        <td>
                            <a href="{{ url('/timetable?batch='.$batch->id) }}">Timetable</a>
                            <x-btn size="sm" variant="secondary" wire:click="deleteBatch({{ $batch->id }})">Delete</x-btn>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    @if ($moveId)
        <x-card title="Move student to another batch">
            <div class="grid-cards">
                <x-select name="moveTo" label="Target batch" :options="$batchOptions->toArray()" placeholder="Select batch" wire:model="moveTo" />
            </div>
            <div style="display:flex; gap: var(--space-2);">
                <x-btn variant="primary" wire:click="doMove">Move</x-btn>
                <x-btn variant="secondary" wire:click="$set('moveId', null)">Cancel</x-btn>
            </div>
        </x-card>
    @endif

    <x-card title="Unassigned students">
        @if ($unassigned->isEmpty())
            <x-state title="All assigned">Every live enrollment is in a batch.</x-state>
        @else
            <x-data-table :head="['Name', 'Course', 'Assign to batch', '']">
                @foreach ($unassigned as $en)
                    <tr wire:key="unassigned-{{ $en->id }}">
                        <td>{{ $en->student?->name }}</td>
                        <td>{{ $en->course?->name }}</td>
                        <td><x-select name="assignTo.{{ $en->id }}" :options="$batchOptions->toArray()" placeholder="Select batch" wire:model="assignTo.{{ $en->id }}" /></td>
                        <td><x-btn size="sm" variant="primary" wire:click="assign({{ $en->id }})">Assign</x-btn></td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
