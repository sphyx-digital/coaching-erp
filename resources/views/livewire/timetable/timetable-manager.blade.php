<div class="stack" style="gap: var(--space-5);">
    <x-page-header title="Timetable" />

    @error('slot')<div class="pill pill--danger"><span class="pill__dot"></span> {{ $message }}</div>@enderror

    <x-card>
        <div class="grid-cards">
            <x-select name="mode" label="View by" :options="['batch' => 'Batch', 'teacher' => 'Teacher', 'room' => 'Room']" wire:model.live="mode" />
            @if ($mode === 'batch')
                <x-select name="batch" label="Batch" :options="$batches->toArray()" placeholder="Select batch" wire:model.live="batch" />
            @elseif ($mode === 'teacher')
                <x-select name="viewTeacher" label="Teacher" :options="$teachers->toArray()" placeholder="Select teacher" wire:model.live="viewTeacher" />
            @else
                <x-select name="viewRoom" label="Room" :options="$rooms->toArray()" placeholder="Select room" wire:model.live="viewRoom" />
            @endif
        </div>
    </x-card>

    @if ($mode === 'batch' && $batch)
        <x-card title="Add a slot">
            <form wire:submit="addSlot">
                <div class="grid-cards">
                    <x-select name="day_of_week" label="Day" :options="$days" wire:model="day_of_week" required />
                    <x-field name="start_time" label="Start" type="time" wire:model="start_time" required />
                    <x-field name="end_time" label="End" type="time" wire:model="end_time" required />
                    <x-select name="subject_id" label="Subject" :options="$subjects->toArray()" placeholder="—" wire:model="subject_id" />
                    <x-select name="teacher_id" label="Teacher" :options="$teachers->toArray()" placeholder="—" wire:model="teacher_id" />
                    <x-select name="classroom_id" label="Room" :options="$rooms->toArray()" placeholder="—" wire:model="classroom_id" />
                </div>
                <x-btn type="submit" variant="primary">Add slot</x-btn>
            </form>
        </x-card>
    @endif

    <div class="table-wrap">
        <div style="display:grid; grid-template-columns: repeat(7, minmax(140px, 1fr)); gap: 1px; background: var(--border);">
            @foreach ($days as $num => $label)
                <div style="background: var(--surface);">
                    <div style="padding: var(--space-2) var(--space-3); font-weight: var(--weight-semibold); background: var(--surface-sunken); border-bottom: 1px solid var(--border);">{{ $label }}</div>
                    <div style="padding: var(--space-2); display:flex; flex-direction:column; gap: var(--space-2); min-height: 80px;">
                        @forelse (($slots[$num] ?? []) as $slot)
                            <div style="border:1px solid var(--border); border-radius: var(--radius-md); padding: var(--space-2); font-size: var(--text-xs);">
                                <strong>{{ \Illuminate\Support\Str::of($slot->start_time)->substr(0,5) }}–{{ \Illuminate\Support\Str::of($slot->end_time)->substr(0,5) }}</strong><br>
                                {{ $slot->subject?->name ?: 'Class' }}
                                @if ($mode !== 'batch')<br><span class="field__hint">{{ $slot->batch?->name }}</span>@endif
                                @if ($slot->teacher && $mode !== 'teacher')<br><span class="field__hint">{{ $slot->teacher->name }}</span>@endif
                                @if ($slot->classroom && $mode !== 'room')<br><span class="field__hint">{{ $slot->classroom->name }}</span>@endif
                                @if ($mode === 'batch')
                                    <br><a href="#" wire:click.prevent="removeSlot({{ $slot->id }})" style="font-size: var(--text-xs); color: var(--danger);">remove</a>
                                @endif
                            </div>
                        @empty
                            <span class="field__hint" style="text-align:center;">—</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
