<?php

namespace App\Services;

use App\Models\AgentIntent;
use Illuminate\Support\Facades\Cache;

/**
 * Maps a help-agent question to an intent using the triggers in agent_intents.
 *
 * Matching is whole-word/phrase only (never inside other words) and ignores
 * stopwords, so "today" can't turn a team question into the time intent and
 * "hi" can't match inside "which". Scores:
 *   - phrase trigger (2+ words) containing a real keyword ....... 3
 *   - phrase trigger made only of stopwords ("how am i") ......... 2
 *   - single distinctive word (6+ letters, e.g. "blocker") ....... 2
 *   - single short word (e.g. "task") ............................ 1
 *   - single stopword trigger ("today", "help", "when") .......... ignored
 * The best intent wins only with a score of at least MIN_SCORE; otherwise the
 * question goes to the AI answer stage.
 *
 * time_date and greeting are never scored from triggers: they only match the
 * explicit patterns below ("what time is it", a message that is just "hi").
 */
class IntentMatcherService
{
    public const MIN_SCORE = 2;

    private const CACHE_KEY = 'agent_intents';
    private const CACHE_TTL = 600;

    /** Words that carry no intent on their own. */
    private const STOPWORDS = [
        'a', 'an', 'the', 'my', 'me', 'i', 'im', 'we', 'our', 'us', 'you', 'your', 'it', 'its', 'this', 'that',
        'is', 'am', 'are', 'was', 'be', 'do', 'does', 'did', 'have', 'has', 'can', 'could', 'will', 'would', 'should',
        'how', 'who', 'what', 'which', 'when', 'where', 'why', 'whats',
        'to', 'of', 'in', 'on', 'at', 'for', 'with', 'from', 'about', 'by', 'and', 'or', 'any', 'there', 'some',
        'today', 'now', 'right', 'day', 'please', 'tell', 'show', 'give', 'get', 'know', 'let', 'help', 'need',
        'doing', 'going', 'much', 'many', 'all', 'so', 'just', 'still', 'yet', 'up',
    ];

    /** Explicit time/date asks — the only way to reach time_date. Run on normalized text. */
    private const TIME_PATTERNS = [
        '/\bwhat time is it\b/',
        '/\bwhat is the (current )?time\b/',
        '/\b(current|exact) time\b/',
        '/\btime (is it )?right now\b/',
        '/\bwhat is the (current )?date\b/',
        '/\bwhat date is (it|today)\b/',
        '/\bwhat is todays date\b/',
        '/\btodays date\b/',
        '/\bwhat day is (it|today)\b/',
        '/\bwhich day is (it|today)\b/',
    ];

    /** A message that is only a greeting ("hi", "good morning outy!"). */
    private const GREETING_PATTERN = '/^(hi+|hello+|hey+|hiya|namaste|hola|howdy|greetings|yo|good (morning|afternoon|evening|night)|whats up|sup)( (there|outy|team|all|everyone))?$/';

    private const TYPOS = [
        'wat ' => 'what ', 'wht ' => 'what ', 'waht ' => 'what ', 'whats ' => 'what is ', 'what it ' => 'what is ',
        'suggessions' => 'suggestions', 'sugestions' => 'suggestions', 'reccomend' => 'recommend', 'recomend' => 'recommend',
        'performence' => 'performance', 'perfomance' => 'performance', 'manger' => 'manager', 'taks ' => 'task ',
        'spint ' => 'sprint ', 'blokcer' => 'blocker', 'bolcker' => 'blocker', 'scroe' => 'score', 'feeback' => 'feedback',
    ];

    // ── Public API ────────────────────────────────────────────────────────────

    /** Intent name, or 'unknown' when nothing passes MIN_SCORE. */
    public static function classify(string $input): string
    {
        return self::match($input)['intent'];
    }

    /**
     * @return array{intent: string, score: int, matched: string[], candidate: ?string}
     *         candidate = best intent even when it fell below the threshold
     */
    public static function match(string $input): array
    {
        $q = self::normalize($input);

        if ($q === '') {
            return ['intent' => 'unknown', 'score' => 0, 'matched' => [], 'candidate' => null];
        }

        foreach (self::TIME_PATTERNS as $pattern) {
            if (preg_match($pattern, $q)) {
                return ['intent' => 'time_date', 'score' => 99, 'matched' => ['explicit time/date question'], 'candidate' => 'time_date'];
            }
        }

        if (preg_match(self::GREETING_PATTERN, $q)) {
            return ['intent' => 'greeting', 'score' => 99, 'matched' => ['greeting only'], 'candidate' => 'greeting'];
        }

        $best = ['intent' => 'unknown', 'score' => 0, 'matched' => [], 'longest' => 0];

        foreach (self::getIntents() as $intent) {
            if (in_array($intent['intent_name'], ['time_date', 'greeting'], true)) {
                continue;
            }

            [$score, $matched, $longest] = self::scoreIntent($q, $intent['triggers']);

            if ($score > $best['score'] || ($score === $best['score'] && $score > 0 && $longest > $best['longest'])) {
                $best = ['intent' => $intent['intent_name'], 'score' => $score, 'matched' => $matched, 'longest' => $longest];
            }
        }

        $passes = $best['score'] >= self::MIN_SCORE;

        return [
            'intent'    => $passes ? $best['intent'] : 'unknown',
            'score'     => $best['score'],
            'matched'   => $best['matched'],
            'candidate' => $best['score'] > 0 ? $best['intent'] : null,
        ];
    }

    public static function getIntents(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn () => AgentIntent::active()
                ->get()
                ->map(fn ($i) => [
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

    // ── Scoring ───────────────────────────────────────────────────────────────

    /** @return array{0:int, 1:string[], 2:int} score, matched triggers, longest match length */
    private static function scoreIntent(string $q, array $triggers): array
    {
        // Longest triggers first, so a phrase claims its words before single-word triggers do
        $triggers = array_values(array_unique(array_filter(array_map([self::class, 'normalize'], $triggers))));
        usort($triggers, fn ($a, $b) => strlen($b) <=> strlen($a));

        $score   = 0;
        $matched = [];
        $covered = [];
        $longest = 0;

        foreach ($triggers as $trigger) {
            $words      = explode(' ', $trigger);
            $meaningful = array_values(array_diff($words, self::STOPWORDS));
            $isPhrase   = count($words) > 1;

            if (!$isPhrase && !$meaningful) {
                continue; // single stopword trigger ("today", "help", "when")
            }
            if (!self::containsTerm($q, $trigger)) {
                continue;
            }
            // Words already counted through a longer phrase don't score again
            if ($meaningful && !array_diff($meaningful, $covered)) {
                continue;
            }

            $score += match (true) {
                $isPhrase && $meaningful         => 3,
                $isPhrase                        => 2,
                strlen($trigger) >= 6            => 2,
                default                          => 1,
            };

            $covered   = array_merge($covered, $meaningful);
            $matched[] = $trigger;
            $longest   = max($longest, strlen($trigger));
        }

        return [$score, $matched, $longest];
    }

    /** Whole word/phrase match; the last word may take a plural "s"/"es". */
    private static function containsTerm(string $q, string $term): bool
    {
        return (bool) preg_match('/(?<![a-z0-9])' . preg_quote($term, '/') . '(?:s|es)?(?![a-z0-9])/', $q);
    }

    /** Lowercase, fix common typos, drop punctuation, collapse spaces. */
    private static function normalize(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = str_replace(['’', '‘', '`'], "'", $t);
        $t = preg_replace("/\b(what|who|how|where|that|it)'s\b/", '$1 is', $t);
        $t = preg_replace("/'s\b/", 's', $t);          // today's → todays
        $t = str_replace("'", '', $t);                 // don't → dont
        $t = preg_replace('/[^a-z0-9\s]+/', ' ', $t);  // punctuation → space
        $t = preg_replace('/\s+/', ' ', trim($t)) . ' ';
        $t = str_replace(array_keys(self::TYPOS), array_values(self::TYPOS), $t);

        return trim($t);
    }
}
