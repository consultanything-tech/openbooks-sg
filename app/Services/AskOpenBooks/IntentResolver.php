<?php

namespace App\Services\AskOpenBooks;

/**
 * Maps a layman question to a catalog intent key.
 * Order: keyword matcher first (free, instant), then LLM refinement when the
 * keyword match is weak/absent and an API key is configured. The LLM may only
 * return keys from the catalog enum — anything else is discarded.
 */
class IntentResolver
{
    public function __construct(protected LlmClient $llm) {}

    public function resolve(string $question): ?string
    {
        $keyword = QuestionCatalog::matchIntent($question);
        if ($keyword !== null) {
            return $keyword;
        }

        if (! $this->llm->isConfigured()) {
            return null;
        }

        $enum = collect(QuestionCatalog::all())
            ->map(fn ($q) => $q['key'].': '.$q['label'])
            ->implode("\n");

        $system = 'You are the intent router for OpenBooks, a Singapore accounting app. '
            .'Classify the user question into exactly one intent from the list below. '
            .'If none fits, return {"key": null}. Reply with strict JSON only, no prose.';

        $user = "Intents:\n".$enum."\n\nUser question: ".$question."\n\nReply JSON: {\"key\": \"...\"}";

        $raw = $this->llm->complete($system, $user, 60, 0.0);
        if ($raw === null) {
            return null;
        }

        $key = $this->extractKey($raw);

        return ($key !== null && in_array($key, QuestionCatalog::keys(), true)) ? $key : null;
    }

    private function extractKey(string $raw): ?string
    {
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json) && array_key_exists('key', $json)) {
                return is_string($json['key']) ? $json['key'] : null;
            }
        }
        $raw = trim($raw, "\"' \n");

        return in_array($raw, QuestionCatalog::keys(), true) ? $raw : null;
    }
}
