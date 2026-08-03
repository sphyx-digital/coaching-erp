<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper over the Anthropic Messages API. Configured via
 * config/services.php (ANTHROPIC_API_KEY / ANTHROPIC_MODEL). Read-only: it only
 * sends prompts and returns the assistant's text.
 */
class AnthropicClient
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.anthropic.key'));
    }

    /**
     * Send a system prompt + a list of user/assistant messages, return the text.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     */
    public function message(string $system, array $messages): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('The AI copilot is not configured. Add ANTHROPIC_API_KEY to the environment.');
        }

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => config('services.anthropic.version'),
            'content-type' => 'application/json',
        ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => config('services.anthropic.max_tokens'),
            'system' => $system,
            'messages' => array_map(fn ($m) => [
                'role' => $m['role'],
                'content' => $m['content'],
            ], $messages),
        ]);

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? 'The AI service returned an error.';
            throw new RuntimeException($msg);
        }

        // Concatenate any text blocks in the content array.
        return collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n") ?: 'No response.';
    }
}
