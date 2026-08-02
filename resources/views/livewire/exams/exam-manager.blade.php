<div class="stack">
    <x-page-header title="Online exams">
        <x-slot:actions>
            <button class="btn btn--primary" wire:click="openCreate">New exam</button>
        </x-slot:actions>
    </x-page-header>

    @if (session('exam_saved'))<div class="alert alert--success" role="status">Exam saved.</div>@endif
    @error('publish')<div class="alert alert--danger" role="alert">{{ $message }}</div>@enderror

    <x-card>
        @if ($exams->isEmpty())
            <x-state title="No exams yet">Create an online exam, add questions, then publish it for students to attempt.</x-state>
        @else
            <x-data-table :head="['Exam', 'Course', 'Questions', 'Marks', 'Attempts', 'Status', '']">
                @foreach ($exams as $e)
                    <tr wire:key="ex-{{ $e->id }}">
                        <td><b>{{ $e->title }}</b><div class="field__hint">{{ $e->duration_minutes }} min · pass {{ $e->pass_percentage }}%{{ $e->negative_marking ? ' · negative' : '' }}</div></td>
                        <td>{{ $e->course?->name ?? 'All' }}</td>
                        <td class="num">{{ $e->questions_count }}</td>
                        <td class="num">{{ $e->total_marks }}</td>
                        <td class="num">{{ $e->attempts_count }}</td>
                        <td>
                            @php($v = ['draft'=>'muted','published'=>'success','closed'=>'warning'][$e->status] ?? 'muted')
                            <x-pill variant="{{ $v }}">{{ ucfirst($e->status) }}</x-pill>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button class="btn btn--sm btn--secondary" wire:click="openResults({{ $e->id }})">Results</button>
                                <button class="btn btn--sm btn--secondary" wire:click="openBuilder({{ $e->id }})">Questions</button>
                                @if ($e->status !== 'published')<button class="btn btn--sm btn--secondary" wire:click="openEdit({{ $e->id }})">Edit</button>@endif
                                @if ($e->status === 'draft')<button class="btn btn--sm btn--primary" wire:click="publish({{ $e->id }})">Publish</button>@endif
                                @if ($e->status === 'published')<button class="btn btn--sm btn--secondary" wire:click="close({{ $e->id }})">Close</button>@endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Exam modal ------------------------------------------------------- --}}
    <x-modal wire:model="showExam" title="{{ $editingExam ? 'Edit exam' : 'New exam' }}" wide>
        <x-field name="exam.title" label="Exam title" wire:model="exam.title" required />
        <div class="form-grid form-grid--2">
            <x-select name="exam.course_id" label="Course" :options="$courses->pluck('name','id')->all()" placeholder="All courses" wire:model="exam.course_id" />
            <x-field name="exam.duration_minutes" label="Duration (minutes)" wire:model="exam.duration_minutes" numeric required />
        </div>
        <div class="form-grid form-grid--3">
            <x-field name="exam.pass_percentage" label="Pass %" wire:model="exam.pass_percentage" numeric required />
            <div class="field">
                <label class="field__label">Starts at</label>
                <input type="datetime-local" class="input" wire:model="exam.starts_at">
            </div>
            <div class="field">
                <label class="field__label">Ends at</label>
                <input type="datetime-local" class="input" wire:model="exam.ends_at">
            </div>
        </div>
        <div style="display:flex;gap:24px;margin:6px 0;">
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;"><input type="checkbox" wire:model="exam.negative_marking"><span>Negative marking</span></label>
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;"><input type="checkbox" wire:model="exam.shuffle_questions"><span>Shuffle questions</span></label>
        </div>
        <div class="field">
            <label class="field__label" for="ex_instr">Instructions</label>
            <textarea id="ex_instr" class="input" rows="3" wire:model="exam.instructions"></textarea>
        </div>
        <x-slot:footer>
            <button class="btn" wire:click="$set('showExam', false)">Cancel</button>
            <button class="btn btn--primary" wire:click="saveExam">Save exam</button>
        </x-slot:footer>
    </x-modal>

    {{-- Question builder modal ------------------------------------------- --}}
    <x-modal wire:model="showBuilder" title="Questions — {{ $builderExam?->title }}" wide>
        @if ($builderExam)
            @if (session('q_added'))<div class="alert alert--success" role="status">Question added.</div>@endif

            <div style="margin-bottom:var(--space-4);">
                <h3 style="font-size:14px;margin:0 0 8px;">Current questions ({{ $builderExam->questions->count() }}) · {{ $builderExam->total_marks }} marks</h3>
                @forelse ($builderExam->questions as $i => $q)
                    <div wire:key="bq-{{ $q->id }}" style="display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
                        <div style="font-size:14px;"><b>Q{{ $i+1 }}.</b> {{ \Illuminate\Support\Str::limit(strip_tags($q->body), 90) }}
                            <div class="field__hint">Correct: {{ $q->correct_option }} · {{ $q->pivot->marks ?? $q->marks }} marks</div>
                        </div>
                        <button class="btn btn--sm" wire:click="removeQuestion({{ $q->id }})">Remove</button>
                    </div>
                @empty
                    <p class="field__hint">No questions yet. Add one below.</p>
                @endforelse
            </div>

            <div style="border-top:2px solid var(--border-strong);padding-top:var(--space-3);">
                <h3 style="font-size:14px;margin:0 0 8px;">Add a question</h3>
                <div class="field">
                    <label class="field__label" for="q_body">Question <span style="color:var(--danger)">*</span></label>
                    <textarea id="q_body" class="input" rows="2" wire:model="newQ.body"></textarea>
                    @error('newQ.body')<span class="field__error">{{ $message }}</span>@enderror
                </div>
                <div class="form-grid form-grid--2">
                    @foreach (['A','B','C','D'] as $k)
                        <div class="field">
                            <label class="field__label">Option {{ $k }} @if(in_array($k,['A','B']))<span style="color:var(--danger)">*</span>@endif</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="radio" wire:model="newQ.correct" value="{{ $k }}" title="Mark {{ $k }} correct" aria-label="Mark option {{ $k }} correct">
                                <input class="input" wire:model="newQ.{{ $k }}" placeholder="Option {{ $k }}">
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('newQ.correct')<span class="field__error">{{ $message }}</span>@enderror
                <div class="form-grid form-grid--2">
                    <x-field name="newQ.marks" label="Marks (correct)" wire:model="newQ.marks" numeric />
                    <x-field name="newQ.negative_marks" label="Negative marks (wrong)" wire:model="newQ.negative_marks" numeric />
                </div>
                <button class="btn btn--primary" wire:click="addQuestion">Add question</button>
            </div>
        @endif
        <x-slot:footer>
            <button class="btn" wire:click="$set('showBuilder', false)">Done</button>
        </x-slot:footer>
    </x-modal>

    {{-- Results modal --------------------------------------------------- --}}
    <x-modal wire:model="showResults" title="Results — {{ $resultsExam?->title }}" wide>
        @if ($resultsExam && $analytics)
            <div class="kpi-row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:var(--space-4);">
                <x-kpi label="Attempts" :value="$analytics['attempts']" />
                <x-kpi label="Average" :value="$analytics['average'].' / '.$analytics['max_score']" />
                <x-kpi label="Highest" :value="$analytics['highest']" />
                <x-kpi label="Pass rate" :value="$analytics['pass_rate'].'%'" />
            </div>

            <h3 style="font-size:14px;margin:0 0 8px;">Attempts</h3>
            @if ($attempts->isEmpty())
                <p class="field__hint">No submitted attempts yet.</p>
            @else
                <x-data-table :head="['Student', 'Score', 'Correct', 'Wrong', 'Blank', 'Result']">
                    @foreach ($attempts as $a)
                        <tr wire:key="ra-{{ $a->id }}">
                            <td>{{ $a->student?->name }}</td>
                            <td class="num">{{ $a->score }} / {{ $a->max_score }} ({{ $a->percentage() }}%)</td>
                            <td class="num">{{ $a->correct_count }}</td>
                            <td class="num">{{ $a->wrong_count }}</td>
                            <td class="num">{{ $a->unanswered_count }}</td>
                            <td>@if ($a->passed())<x-pill variant="success">Pass</x-pill>@else<x-pill variant="danger">Fail</x-pill>@endif</td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif

            <h3 style="font-size:14px;margin:var(--space-4) 0 8px;">Question difficulty</h3>
            @if (empty($qStats) || $qStats->isEmpty())
                <p class="field__hint">Difficulty appears once questions have been answered.</p>
            @else
                <x-data-table :head="['#', 'Question', 'Answered', 'Correct %']">
                    @foreach ($resultsExam->questions as $i => $q)
                        @php($st = $qStats[$q->id] ?? null)
                        <tr wire:key="rq-{{ $q->id }}">
                            <td class="num">{{ $i+1 }}</td>
                            <td>{{ \Illuminate\Support\Str::limit(strip_tags($q->body), 70) }}</td>
                            <td class="num">{{ $st['answered'] ?? 0 }}</td>
                            <td class="num">{{ $st['correct_rate'] ?? 0 }}%</td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        @endif
        <x-slot:footer>
            <button class="btn" wire:click="$set('showResults', false)">Close</button>
        </x-slot:footer>
    </x-modal>
</div>
