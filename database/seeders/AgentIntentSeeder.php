<?php

namespace Database\Seeders;

use App\Models\AgentIntent;
use App\Services\IntentMatcherService;
use Illuminate\Database\Seeder;

/**
 * Outy's intents and their trigger words/phrases.
 *
 * How triggers are scored (see IntentMatcherService): whole words/phrases only,
 * stopwords ("my", "today", "help", "how"…) never count on their own, phrases
 * beat single words, and a question needs a score of 2+ to match an intent.
 * Prefer specific phrases ("leave balance") over generic words ("new").
 *
 * time_date and greeting are matched by fixed patterns in IntentMatcherService;
 * their triggers here are only shown in Settings → AI Configuration.
 *
 * Safe to re-run: updates each intent by name.
 */
class AgentIntentSeeder extends Seeder
{
    public function run(): void
    {
        $intents = [
            [
                'intent_name'  => 'time_date',
                'display_name' => 'Time & Date',
                'description'  => 'Explicit questions about the current time or date',
                'triggers'     => ['what time is it', 'current time', 'what is the date', "today's date", 'what day is it'],
            ],
            [
                'intent_name'  => 'greeting',
                'display_name' => 'Greeting',
                'description'  => 'A message that is only a greeting',
                'triggers'     => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'namaste'],
            ],
            [
                'intent_name'  => 'team_status',
                'display_name' => 'My Team Today',
                'description'  => "Live status of the user's team: work logged today, open blockers, who is on leave",
                'triggers'     => [
                    'my team', 'our team', 'how is my team', 'team doing', 'team today', 'team status', 'team update',
                    'needs help', 'need help', 'who needs help', 'anyone struggling',
                    'who is blocked', 'anyone blocked', 'team blocker', 'blockers in my team', 'blocked in my team',
                    'team performance', 'who is on leave', 'on leave today', 'out today',
                ],
            ],
            [
                'intent_name'  => 'my_leave',
                'display_name' => 'My Leave',
                'description'  => 'Leave balance and leave requests of the user',
                'triggers'     => [
                    'leave balance', 'my leave', 'leaves left', 'leave left', 'remaining leave', 'holidays left',
                    'pending leave', 'leave request', 'leave status', 'my leaves', 'leave application',
                    'how many leaves', 'leaves do i have',
                ],
            ],
            [
                'intent_name'  => 'team_size',
                'display_name' => 'Team Size',
                'description'  => 'How many people are in the team or organization',
                'triggers'     => [
                    'team size', 'how many people', 'how many members', 'how many employees', 'member count',
                    'total members', 'total employees', 'headcount', 'staff count', 'workforce', 'how big is',
                ],
            ],
            [
                'intent_name'  => 'my_manager',
                'display_name' => 'My Manager',
                'description'  => 'Who the user reports to',
                'triggers'     => [
                    'manager', 'boss', 'supervisor', 'report to', 'reporting to', 'who manages me', 'line manager', 'my lead',
                ],
            ],
            [
                'intent_name'  => 'my_reports',
                'display_name' => 'My Direct Reports',
                'description'  => 'Who reports to the user',
                'triggers'     => [
                    'my report', 'direct report', 'reportee', 'subordinate', 'reports to me', 'under me', 'i manage',
                ],
            ],
            [
                'intent_name'  => 'my_tasks',
                'display_name' => 'My Tasks',
                'description'  => "The user's open tasks",
                'triggers'     => [
                    'my tasks', 'tasks do i have', 'pending tasks', 'tasks pending', 'open tasks', 'current task',
                    'assigned to me', 'todo', 'to do list', 'work items', 'my tickets', 'what do i have',
                ],
            ],
            [
                'intent_name'  => 'my_score',
                'display_name' => 'My Performance Score',
                'description'  => "The user's performance / increment score",
                'triggers'     => [
                    'my score', 'increment score', 'performance score', 'my performance', 'how am i', 'my rating',
                    'my kpi', 'am i doing well', 'evaluation',
                ],
            ],
            [
                'intent_name'  => 'logged_today',
                'display_name' => 'Did I Log Today',
                'description'  => 'Whether the user logged work today',
                'triggers'     => ['did i log', 'have i logged', 'my log', 'logged today', 'did i submit', 'today log', 'my entry'],
            ],
            [
                'intent_name'  => 'team_logged',
                'display_name' => 'Team Log Status',
                'description'  => 'Who has or has not logged work today',
                'triggers'     => [
                    'who logged', 'not logged', 'hasnt logged', 'havent logged', 'missing log', 'team log',
                    'who did not log', 'how many logged', 'absentee',
                ],
            ],
            [
                'intent_name'  => 'improve_score',
                'display_name' => 'Improve Score / Suggestions',
                'description'  => 'Tips to improve performance',
                'triggers'     => [
                    'improve', 'suggestion', 'advice', 'recommendation', 'tips', 'boost my score', 'increase score',
                    'areas to improve', 'get better', 'raise my score',
                ],
            ],
            [
                'intent_name'  => 'blockers',
                'display_name' => 'My Blockers',
                'description'  => "The user's own blockers",
                'triggers'     => [
                    'blocker', 'blocked', 'blocking', 'obstacle', 'stuck', 'impediment', 'dependency', 'waiting for',
                    'unresolved', 'escalated',
                ],
            ],
            [
                'intent_name'  => 'my_streak',
                'display_name' => 'My Streak',
                'description'  => 'Consecutive days of logging',
                'triggers'     => ['streak', 'days in a row', 'in a row', 'logging streak', 'consecutive'],
            ],
            [
                'intent_name'  => 'sprint_status',
                'display_name' => 'Sprint Status',
                'description'  => 'Progress of the current sprint',
                'triggers'     => ['sprint', 'iteration', 'sprint progress', 'sprint deadline', 'current sprint', 'active sprint'],
            ],
            [
                'intent_name'  => 'my_feedback',
                'display_name' => 'My Feedback',
                'description'  => 'Feedback and reviews the user received',
                'triggers'     => ['feedback', 'appraisal', 'my review', 'manager feedback', 'performance feedback', 'how am i rated'],
            ],
            [
                'intent_name'  => 'missed_notifs',
                'display_name' => 'What Did I Miss',
                'description'  => 'Recent notifications and updates',
                'triggers'     => ['what did i miss', 'anything new', 'catch up', 'fill me in', 'what happened', 'notifications', 'while i was away'],
            ],
            [
                'intent_name'  => 'org_health',
                'display_name' => 'Org Health',
                'description'  => 'Organization-wide health overview',
                'triggers'     => [
                    'org health', 'team health', 'org status', 'company status', 'how are we', 'daily report',
                    'org report', 'health score', 'organization health',
                ],
            ],
            [
                'intent_name'  => 'onboarding',
                'display_name' => 'Getting Started',
                'description'  => 'New user asking how to get started with OutraqHQ',
                'triggers'     => [
                    'get started', 'getting started', 'how to use outraqhq', 'how to use this', 'new here',
                    'first time', 'onboard', 'tutorial', 'where do i start', 'how do i start',
                ],
            ],
        ];

        foreach ($intents as $intent) {
            AgentIntent::updateOrCreate(
                ['intent_name' => $intent['intent_name']],
                $intent + ['is_active' => true]
            );
        }

        IntentMatcherService::clearCache();
    }
}
