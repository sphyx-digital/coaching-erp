<div class="stack">
    @if ($error)
        <x-card><x-state title="Exam unavailable">{{ $error }}</x-state></x-card>
        <a class="btn" href="{{ route('portal.exams') }}">Back to exams</a>
    @elseif ($submitted && $attempt)
        {{-- ---------- Result & review ---------- --}}
        <x-card>
            <div style="text-align:center;padding:var(--space-3) 0;">
                <div class="field__hint">{{ $exam->title }}</div>
                <div style="font-family:var(--font-heading);font-size:40px;font-weight:700;color:{{ $attempt->passed() ? 'var(--success)' : 'var(--danger)' }};">
                    {{ $attempt->score }} / {{ $attempt->max_score }}
                </div>
                <div style="margin-top:6px;">
                    <x-pill variant="{{ $attempt->passed() ? 'success' : 'danger' }}">{{ $attempt->passed() ? 'Passed' : 'Not passed' }} · {{ $attempt->percentage() }}%</x-pill>
                </div>
                <div class="field__hint" style="margin-top:10px;">
                    {{ $attempt->correct_count }} correct · {{ $attempt->wrong_count }} wrong · {{ $attempt->unanswered_count }} unanswered
                </div>
            </div>
        </x-card>

        <x-card title="Review answers">
            @foreach ($questions as $i => $q)
                @php($ans = $reviewAnswers->get($q->id))
                <div wire:key="rev-{{ $q->id }}" style="padding:14px 0;border-bottom:1px solid var(--border);">
                    <div style="font-weight:600;font-size:14px;margin-bottom:8px;">Q{{ $i+1 }}. {{ $q->body }}
                        <span class="num field__hint">({{ $ans?->marks_awarded ?? 0 }} marks)</span>
                    </div>
                    @foreach ($q->options as $opt)
                        @php($isCorrect = $opt['key'] === $q->correct_option)
                        @php($isChosen = $ans && $ans->selected_option === $opt['key'])
                        <div style="padding:6px 10px;border-radius:8px;margin-bottom:4px;font-size:14px;
                            {{ $isCorrect ? 'background:color-mix(in srgb,var(--success) 14%,var(--surface));' : ($isChosen ? 'background:color-mix(in srgb,var(--danger) 12%,var(--surface));' : '') }}">
                            <b>{{ $opt['key'] }}.</b> {{ $opt['text'] }}
                            @if ($isCorrect) <x-pill variant="success">Correct answer</x-pill>@endif
                            @if ($isChosen && ! $isCorrect) <x-pill variant="danger">Your answer</x-pill>@endif
                            @if ($isChosen && $isCorrect) <x-pill variant="success">Your answer</x-pill>@endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </x-card>
        <a class="btn" href="{{ route('portal.exams') }}">Back to exams</a>

    @else
        {{-- ---------- Take the exam ---------- --}}
        <div x-data="{
                left: {{ $secondsRemaining }},
                get mmss() { const m = Math.floor(this.left/60), s = this.left%60; return (m<10?'0':'')+m+':'+(s<10?'0':'')+s; },
                init() {
                    if (this.left <= 0) { $wire.submitExam(); return; }
                    this.timer = setInterval(() => {
                        this.left--;
                        if (this.left <= 0) { clearInterval(this.timer); $wire.submitExam(); }
                    }, 1000);
                }
             }"
             x-init="init()">
            <x-card>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div>
                        <h2 style="margin:0;font-size:18px;">{{ $exam->title }}</h2>
                        <div class="field__hint">{{ $questions->count() }} questions · {{ $exam->total_marks }} marks {{ $exam->negative_marking ? '· negative marking' : '' }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="field__hint">Time left</div>
                        <div class="num" style="font-family:var(--font-heading);font-size:26px;font-weight:700;" x-text="mmss"
                             :style="left < 60 ? 'color:var(--danger)' : ''">--:--</div>
                    </div>
                </div>
                @if ($exam->instructions)
                    <p class="field__hint" style="margin-top:10px;">{{ $exam->instructions }}</p>
                @endif
            </x-card>

            <div class="stack" style="margin-top:var(--space-3);">
                @foreach ($questions as $i => $q)
                    <x-card>
                        <div style="font-weight:600;font-size:15px;margin-bottom:12px;">Q{{ $i+1 }}. {{ $q->body }}
                            <span class="field__hint num">(+{{ $exam->marksFor($q) }}{!! $exam->negative_marking ? ', &minus;'.$q->negative_marks : '' !!})</span>
                        </div>
                        @foreach ($q->options as $opt)
                            <label wire:key="opt-{{ $q->id }}-{{ $opt['key'] }}"
                                   style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;cursor:pointer;font-size:14px;">
                                <input type="radio" wire:model="answers.{{ $q->id }}" value="{{ $opt['key'] }}">
                                <span><b>{{ $opt['key'] }}.</b> {{ $opt['text'] }}</span>
                            </label>
                        @endforeach
                    </x-card>
                @endforeach
            </div>

            <div style="position:sticky;bottom:0;background:var(--surface);padding:var(--space-3) 0;border-top:1px solid var(--border);margin-top:var(--space-3);display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <span class="field__hint">Answers are graded on submit. You can change them until you submit.</span>
                <button class="btn btn--primary" wire:click="submitExam"
                        wire:confirm="Submit your exam? You cannot change answers after this."
                        wire:loading.attr="disabled">Submit exam</button>
            </div>
        </div>
    @endif
</div>
