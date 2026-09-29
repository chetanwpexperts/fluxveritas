<?php

/*
|--------------------------------------------------------------------------
| Outy — the in-app AI assistant (OpenAI tool calling)
|--------------------------------------------------------------------------
| The API key comes from services.openai.key (OPENAI_API_KEY). Without a key,
| or when the API fails, Outy falls back to the keyword intent matcher.
*/

return [

    'model' => env('OUTY_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini')),

    'timeout' => (int) env('OUTY_TIMEOUT', 25),       // seconds per OpenAI request

    'max_tool_calls' => 5,                             // per question

    'history_turns' => 10,                             // user+assistant pairs kept in the session

    'max_answer_tokens' => 600,

    // Questions per user per day, by the organization's plan. Super admins
    // without an organization use "platform".
    'daily_limit' => [
        'free'       => (int) env('OUTY_DAILY_LIMIT_FREE', 50),
        'pro'        => (int) env('OUTY_DAILY_LIMIT_PRO', 50),
        'enterprise' => (int) env('OUTY_DAILY_LIMIT_ENTERPRISE', 50),
        'platform'   => (int) env('OUTY_DAILY_LIMIT_PLATFORM', 200),
    ],

    // Knowledge files for explain_feature — one markdown file per module
    'docs_path' => base_path('docs/outy'),

];
