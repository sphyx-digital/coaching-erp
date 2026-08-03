<?php

namespace App\Livewire\Copilot;

use App\Services\Ai\CopilotService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Copilot extends Component
{
    public string $question = '';

    /** @var array<int,array{role:string,content:string}> */
    public array $messages = [];

    public const SUGGESTIONS = [
        'Who has the highest outstanding fees?',
        'Summarise this month so far',
        'Which enquiries need a follow-up today?',
        'How is attendance across batches?',
    ];

    public function mount(): void
    {
        abort_if(Auth::user()?->isPortalUser(), 403);
    }

    public function suggest(string $q): void
    {
        $this->question = $q;
        $this->ask(app(CopilotService::class));
    }

    public function ask(CopilotService $copilot): void
    {
        abort_if(Auth::user()?->isPortalUser(), 403);
        $q = trim($this->question);
        if ($q === '') {
            return;
        }

        $history = $this->messages;
        $this->messages[] = ['role' => 'user', 'content' => $q];
        $this->question = '';

        if (! $copilot->isConfigured()) {
            $this->messages[] = ['role' => 'assistant', 'content' => 'The AI copilot is not configured yet. Add an `ANTHROPIC_API_KEY` to the environment to enable it.'];

            return;
        }

        try {
            $answer = $copilot->answer(Auth::user(), $q, $history);
        } catch (\Throwable $e) {
            $answer = 'Sorry — I could not answer that just now. ('.$e->getMessage().')';
        }
        $this->messages[] = ['role' => 'assistant', 'content' => $answer];
    }

    public function clearChat(): void
    {
        $this->messages = [];
    }

    public function render()
    {
        return view('livewire.copilot.copilot', [
            'configured' => app(CopilotService::class)->isConfigured(),
            'suggestions' => self::SUGGESTIONS,
        ]);
    }
}
