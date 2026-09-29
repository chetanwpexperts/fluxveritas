<?php

namespace App\Services\Outy\Tools;

use App\Models\User;
use Illuminate\Support\Str;

/** How a module works, from docs/outy/{topic}.md. */
class ExplainFeature extends OutyTool
{
    protected bool $requiresOrganization = false;

    private const MAX_CHARS = 6000;

    public function name(): string
    {
        return 'explain_feature';
    }

    public function description(): string
    {
        return 'Step-by-step guide to a part of OutraqHQ (how to do something, who can use it, which plan). '
            . 'Call it for any "how do I…" or "what is…" question about the app.';
    }

    public function parameters(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'name' => ['type' => 'string', 'enum' => self::topics(), 'description' => 'Topic to explain'],
            ],
            'required'             => ['name'],
            'additionalProperties' => false,
        ];
    }

    /** @return string[] topic slugs = file names in docs/outy */
    public static function topics(): array
    {
        return collect(glob(rtrim(config('outy.docs_path'), '/') . '/*.md') ?: [])
            ->map(fn ($path) => basename($path, '.md'))
            ->sort()->values()->all();
    }

    protected function handle(User $user, array $args): array
    {
        $topic = Str::slug((string) ($args['name'] ?? ''));

        if (!in_array($topic, self::topics(), true)) {
            return ['error' => 'Unknown topic.', 'available_topics' => self::topics()];
        }

        $text = (string) file_get_contents(rtrim(config('outy.docs_path'), '/') . "/{$topic}.md");

        return ['topic' => $topic, 'guide' => Str::limit($text, self::MAX_CHARS)];
    }
}
