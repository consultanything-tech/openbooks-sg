<?php

namespace App\Services\AskOpenBooks;

/**
 * Turns a computed answer payload into a friendly layman paragraph.
 * The LLM is only allowed to restate the figures it is given — if it is not
 * configured or returns nothing useful, the deterministic template stands.
 */
class AnswerNarrator
{
    public function __construct(protected LlmClient $llm) {}

    public function narrate(array $payload): ?string
    {
        if (! $this->llm->isConfigured()) {
            return null;
        }

        $facts = [
            'question' => $payload['question'],
            'headline' => $payload['headline'],
            'comparison' => $payload['compare']['text'] ?? null,
            'breakdown' => $payload['breakdown']
                ? array_slice($payload['breakdown']['rows'], 0, 8)
                : null,
            'caveats' => $payload['caveats'] ?: null,
        ];

        $system = 'You are OpenBooks, a plain-speaking accounting assistant for Singapore small businesses. '
            .'Explain the provided computed answer in 2-3 short sentences a non-accountant understands. '
            .'Use ONLY the figures provided — never invent or estimate numbers. No markdown, no bullet points.';

        $raw = $this->llm->complete($system, json_encode($facts), 220, 0.2);
        if ($raw === null) {
            return null;
        }

        // Strip any markdown/fences the model may ignore instructions about
        $clean = trim(preg_replace('/[`*#>\[\]]+/', '', $raw));
        $clean = preg_replace('/\s+/', ' ', $clean);

        // Guard against runaway or empty narrations
        if (strlen($clean) < 20 || strlen($clean) > 600) {
            return null;
        }

        return $clean;
    }
}
