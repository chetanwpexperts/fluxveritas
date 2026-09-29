<?php

namespace App\Services;

use App\Models\AgentIntent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntentMatcherService
{
    private const CACHE_KEY = 'agent_intents';
    private const CACHE_TTL = 600;

    private const VALID_INTENTS = [
        'greeting', 'my_identity', 'time_date', 'team_size', 'my_manager', 'my_reports',
        'my_tasks', 'my_score', 'logged_today', 'team_logged', 'improve_score',
        'blockers', 'my_streak', 'sprint_status', 'my_feedback', 'peer_feedback',
        'missed_notifs', 'org_health', 'superadmin_info', 'onboarding',
        'out_of_scope', 'other_work',
    ];

    // ── Main entry point ──────────────────────────────────────────────────────

    public static function classify(string $input): string
    {
        $q = strtolower(trim($input));
        $q = self::normalizeTypos($q);

        // Step 1: fast, free DB trigger check
        $dbIntent = self::checkDbTriggers($q);
        if ($dbIntent !== 'unknown') {
            return $dbIntent;
        }

        // Step 2: AI classification (cheap — 10 output tokens max)
        $aiIntent = self::classifyWithAI($input);
        if ($aiIntent !== 'unknown') {
            return $aiIntent;
        }

        return 'unknown';
    }

    // ── Step 1: DB trigger scoring ────────────────────────────────────────────

    private static function checkDbTriggers(string $q): string
    {
        $intents         = self::getIntents();
        $bestMatch       = 'unknown';
        $bestScore       = 0;
        $outOfScopeScore = 0;

        foreach ($intents as $intent) {
            $score = self::scoreMatch($q, $intent['triggers']);

            if ($intent['intent_name'] === 'out_of_scope') {
                $outOfScopeScore = $score;
                continue;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $intent['intent_name'];
            }
        }

        // Work intent needs a confident score to win
        if ($bestScore >= 4) return $bestMatch;

        // Out of scope only if no work intent matched
        if ($outOfScopeScore >= 6) return 'out_of_scope';

        return 'unknown';
    }

    // ── Step 2: AI classification — Ollama primary, OpenAI fallback ─────────────

    private static function classifyWithAI(string $input): string
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');

        // Ollama first — free, local, always preferred
        $ollamaResult = self::classifyWithOllama($input);
        if ($ollamaResult !== 'unknown') {
            return $ollamaResult;
        }

        // OpenAI fallback only if Ollama failed
        if (!empty($apiKey)) {
            return self::classifyWithOpenAI($input, $apiKey);
        }

        return 'unknown';
    }

    // ── Ollama (primary, local, free) ─────────────────────────────────────────

    private static function classifyWithOllama(string $input): string
    {
        try {
            $intentList = implode("\n", self::VALID_INTENTS);

            $prompt = "You are an intent classifier. "
                . "Pick EXACTLY ONE intent from this list that best matches the user message.\n\n"
                . "INTENT LIST:\n"
                . $intentList . "\n\n"
                . "RULES:\n"
                . "- greeting: hello, hi, good morning, namaste\n"
                . "- my_identity: who am i, who i am, my name, my role, about me\n"
                . "- my_tasks: tasks, todo, assignments, what do i have\n"
                . "- my_score: performance, score, rating, how am i doing\n"
                . "- my_manager: manager, boss, who do i report to\n"
                . "- my_reports: who reports to me, my team, direct reports\n"
                . "- team_size: how many people, team size, member count\n"
                . "- logged_today: did i log, my log today\n"
                . "- team_logged: who logged, team log status\n"
                . "- improve_score: suggestions, tips, advice, improve\n"
                . "- blockers: blocked, blocker, stuck\n"
                . "- sprint_status: sprint, iteration\n"
                . "- my_feedback: feedback, review, appraisal\n"
                . "- missed_notifs: what did i miss, updates, news\n"
                . "- org_health: org health, team status, how is org\n"
                . "- onboarding: how to start, get started, how to use\n"
                . "- out_of_scope: coding, geography, food, sports, movies, finance, general knowledge NOT about work\n"
                . "- other_work: work related but not in above list\n\n"
                . "USER MESSAGE: \"{$input}\"\n\n"
                . "Reply with ONLY the intent name from the list. ONE WORD ONLY. No explanation.";

            $response = Http::timeout(8)->post('http://localhost:11434/api/generate', [
                'model'   => 'llama3.2:1b',
                'prompt'  => $prompt,
                'stream'  => false,
                'options' => [
                    'temperature' => 0,
                    'num_predict' => 10,
                ],
            ]);

            if ($response->successful()) {
                $raw   = strtolower(trim($response->json('response') ?? ''));
                $raw   = preg_replace('/[^a-z_\s]/', '', $raw);
                $words = preg_split('/\s+/', trim($raw));

                // First valid intent wins
                foreach ($words as $word) {
                    $word = trim($word, '_');
                    if (in_array($word, self::VALID_INTENTS)) {
                        return $word;
                    }
                }

                // Try space-to-underscore conversion
                $clean = str_replace(' ', '_', trim($raw));
                if (in_array($clean, self::VALID_INTENTS)) {
                    return $clean;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Ollama classification failed: ' . $e->getMessage());
        }

        return 'unknown';
    }

    // ── OpenAI fallback ───────────────────────────────────────────────────────

    private static function classifyWithOpenAI(string $input, string $apiKey): string
    {
        try {
            $intentList = implode(', ', self::VALID_INTENTS);

            $prompt = "You are an intent classifier for OutraqHQ, a workplace performance management tool.\n\n"
                . "Classify this user message into EXACTLY ONE intent from this list:\n"
                . $intentList . "\n\n"
                . "Rules:\n"
                . "- greeting: hello, hi, good morning etc\n"
                . "- my_identity: who am i, who i am, my name, my role\n"
                . "- my_tasks: asking about their tasks/work items\n"
                . "- my_score: asking about performance score\n"
                . "- my_manager: asking who their manager is\n"
                . "- team_size: asking how many people in team\n"
                . "- improve_score: asking for tips/suggestions\n"
                . "- out_of_scope: coding, geography, food, sports, entertainment, general knowledge NOT related to work\n"
                . "- other_work: work related but not in the list\n\n"
                . "User message: \"{$input}\"\n\n"
                . "Reply with ONLY the intent name. No explanation. No punctuation.";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->timeout(5)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => config('services.openai.intent_model', 'gpt-4o-mini'),
                'max_tokens'  => 10,
                'temperature' => 0,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->successful()) {
                $intent = strtolower(trim(
                    $response->json('choices.0.message.content') ?? ''
                ));
                $intent = preg_replace('/[^a-z_]/', '', $intent);

                if (in_array($intent, self::VALID_INTENTS)) {
                    return $intent;
                }
            }
        } catch (\Exception $e) {
            Log::warning('OpenAI classification failed: ' . $e->getMessage());
        }

        return 'unknown';
    }

    // ── Load intents from DB (cached) ─────────────────────────────────────────

    public static function getIntents(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn() => AgentIntent::active()
                ->get()
                ->map(fn($i) => [
                    'intent_name' => $i->intent_name,
                    'triggers'    => array_map('strtolower', $i->triggers ?? []),
                ])
                ->toArray()
        );
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ── Score match — stricter thresholds than before ─────────────────────────

    private static function scoreMatch(string $q, array $triggers): int
    {
        $score  = 0;
        $qWords = explode(' ', $q);

        foreach ($triggers as $trigger) {
            $trigger = strtolower(trim($trigger));

            // Exact phrase match — double weight
            if (str_contains($q, $trigger)) {
                $score += strlen($trigger) * 2;
                continue;
            }

            // Partial match only for triggers 5+ chars
            if (strlen($trigger) < 5) continue;

            foreach ($qWords as $qWord) {
                if (strlen($qWord) < 4) continue;

                if (str_starts_with($qWord, $trigger)) {
                    $score += strlen($trigger);
                } elseif (str_starts_with($trigger, $qWord) && strlen($qWord) >= 5) {
                    $score += strlen($qWord) - 1;
                }
            }
        }

        return $score;
    }

    // ── Typo normalizer ───────────────────────────────────────────────────────

    private static function normalizeTypos(string $q): string
    {
        $map = [
            'wat '        => 'what ',
            'wht '        => 'what ',
            'waht '       => 'what ',
            'whats '      => 'what is ',
            "what's "     => 'what is ',
            'what it '    => 'what is ',
            'suggessions' => 'suggestions',
            'sugestions'  => 'suggestions',
            'reccomend'   => 'recommend',
            'recomend'    => 'recommend',
            'performence' => 'performance',
            'perfomance'  => 'performance',
            'manger'      => 'manager',
            'taks '       => 'task ',
            'spint '      => 'sprint ',
            'blokcer'     => 'blocker',
            'bolcker'     => 'blocker',
            'scroe'       => 'score',
            'feeback'     => 'feedback',
        ];

        return str_replace(array_keys($map), array_values($map), $q);
    }
}
