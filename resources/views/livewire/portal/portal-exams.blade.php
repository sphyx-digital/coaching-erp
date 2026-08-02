<div class="stack">
    @include('livewire.portal.partials.switcher')

    @if (! $student)
        <x-card><x-state title="No student linked">Nothing to show yet.</x-state></x-card>
    @else
        <x-card title="Online exams">
            @if ($exams->isEmpty())
                <x-state title="No exams available">Published exams for your course will appear here.</x-state>
            @else
                <x-data-table :head="['Exam', 'Questions', 'Duration', 'Status', '']">
                    @foreach ($exams as $exam)
                        @php($attempt = $attempts->get($exam->id))
                        <tr wire:key="pe-{{ $exam->id }}">
                            <td><b>{{ $exam->title }}</b>@if($exam->course)<div class="field__hint">{{ $exam->course->name }}</div>@endif</td>
                            <td class="num">{{ $exam->questions_count }}</td>
                            <td class="num">{{ $exam->duration_minutes }} min</td>
                            <td>
                                @if ($attempt && $attempt->isSubmitted())
                                    <x-pill variant="{{ $attempt->passed() ? 'success' : 'danger' }}">{{ $attempt->score }} / {{ $attempt->max_score }} ({{ $attempt->percentage() }}%)</x-pill>
                                @elseif ($attempt)
                                    <x-pill variant="warning">In progress</x-pill>
                                @elseif ($exam->isOpen())
                                    <x-pill variant="info">Available</x-pill>
                                @else
                                    <x-pill variant="muted">Not open</x-pill>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                @if ($attempt && $attempt->isSubmitted())
                                    <a class="btn btn--sm" href="{{ route('portal.exam', $exam->id) }}">View result</a>
                                @elseif ($exam->isOpen())
                                    <a class="btn btn--sm btn--primary" href="{{ route('portal.exam', $exam->id) }}">{{ $attempt ? 'Resume' : 'Start' }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    @endif
</div>
