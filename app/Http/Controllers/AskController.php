<?php

namespace App\Http\Controllers;

use App\Models\AskQuery;
use App\Services\AskOpenBooks\AnswerNarrator;
use App\Services\AskOpenBooks\AnswerService;
use App\Services\AskOpenBooks\IntentResolver;
use App\Services\AskOpenBooks\QuestionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * "Ask OpenBooks" — read-only, data-grounded Q&A over the company's books.
 * Read-only by design: no handler in AnswerService performs writes, and the
 * route is open to every authenticated role (including VIEWER).
 */
class AskController extends Controller
{
    const DRILL_PATTERN = '/^(why|how so|tell me more|show me (the )?details?|drill|break (it|that) down|what is (behind|in) (that|this|it))/i';

    public function index(): View
    {
        return view('ask.index', [
            'groups' => QuestionCatalog::grouped(),
            'keys' => QuestionCatalog::keys(),
            'recent' => AskQuery::where('user_id', auth()->id())
                ->latest()->limit(5)->get(['question', 'intent_key']),
            'aiConfigured' => (new \App\Services\AskOpenBooks\LlmClient())->isConfigured(),
        ]);
    }

    public function run(
        Request $request,
        AnswerService $service,
        IntentResolver $resolver,
        AnswerNarrator $narrator
    ): JsonResponse {
        $request->validate([
            'key' => 'nullable|string|max:60',
            'question' => 'nullable|string|max:300',
            'context' => 'nullable|string|max:60',
        ]);

        $key = $request->input('key');
        $question = trim((string) $request->input('question', ''));
        $context = (string) $request->input('context', '');

        // "Why?" style follow-ups drill into the row-level detail of the
        // previous answer instead of re-running the aggregate.
        if ($key === null || $key === '') {
            if ($question !== '' && preg_match(self::DRILL_PATTERN, $question) === 1
                && in_array($context, QuestionCatalog::keys(), true)
                && $service->supportsDrill($context)) {
                return $this->respondDrill($service, $context);
            }
        }

        // Scope guard: a key must exist in the catalog, otherwise resolve
        // layman phrasing (keywords first, LLM refinement when configured).
        if ($key !== null && $key !== '') {
            if (!in_array($key, QuestionCatalog::keys(), true)) {
                return $this->outOfScope($question);
            }
        } else {
            if ($question === '') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Type a question or pick one of the suggested questions.',
                ], 422);
            }
            $key = $resolver->resolve($question);
            if ($key === null) {
                return $this->outOfScope($question);
            }
        }

        try {
            $payload = $service->answer($key, $question !== '' ? $question : null);
        } catch (\Exception $e) {
            Log::error('AskOpenBooks failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'I could not compute that answer right now. Please try again.',
            ], 500);
        }

        $narrated = $narrator->narrate($payload);
        if ($narrated !== null) {
            $payload['answer'] = $narrated;
            $payload['narrated'] = true;
        }

        AskQuery::create([
            'user_id' => auth()->id(),
            'question' => mb_substr($payload['question'], 0, 300),
            'intent_key' => $key,
        ]);

        return response()->json(['status' => 'ok', 'payload' => $payload]);
    }

    public function drill(Request $request, AnswerService $service): JsonResponse
    {
        $request->validate(['key' => 'required|string|max:60']);
        $key = (string) $request->input('key');

        if (!in_array($key, QuestionCatalog::keys(), true) || !$service->supportsDrill($key)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No row-level detail is available for that question.',
            ], 422);
        }

        return $this->respondDrill($service, $key);
    }

    private function respondDrill(AnswerService $service, string $key): JsonResponse
    {
        try {
            $payload = $service->drill($key);
        } catch (\Exception $e) {
            Log::error('AskOpenBooks drill failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'I could not pull the details right now. Please try again.',
            ], 500);
        }

        return response()->json(['status' => 'ok', 'payload' => $payload]);
    }

    private function outOfScope(string $question): JsonResponse
    {
        $suggestions = collect(QuestionCatalog::all())
            ->map(fn ($q) => ['key' => $q['key'], 'label' => $q['label']])
            ->values()->take(4)->all();

        return response()->json([
            'status' => 'out_of_scope',
            'message' => $question !== ''
                ? 'I can only answer questions about your own OpenBooks data — cash, receivables, payables, revenue, GST, stock and budgets. I could not match "' . $question . '" to any of them.'
                : 'That question is outside what I can answer from your books.',
            'suggestions' => $suggestions,
        ], 200);
    }
}
