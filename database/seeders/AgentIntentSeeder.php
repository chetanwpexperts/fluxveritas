<?php

namespace Database\Seeders;

use App\Models\AgentIntent;
use Illuminate\Database\Seeder;

class AgentIntentSeeder extends Seeder
{
    public function run(): void
    {
        $intents = [
            [
                'intent_name'  => 'time_date',
                'display_name' => 'Time & Date',
                'description'  => 'User asking about current time or date',
                'triggers'     => [
                    'time', 'date', 'day', 'today', 'clock', 'when',
                    'what time', 'current time',
                    'what date', 'what day', 'right now',
                ],
            ],
            [
                'intent_name'  => 'greeting',
                'display_name' => 'Greeting',
                'description'  => 'User saying hello or good morning/evening',
                'triggers'     => [
                    'hello', 'hi', 'hey', 'good morning', 'good afternoon',
                    'good evening', 'good night', 'howdy', 'greetings',
                    'whats up', 'what up', 'sup', 'namaste', 'hola',
                ],
            ],
            [
                'intent_name'  => 'team_size',
                'display_name' => 'Team Size',
                'description'  => 'User asking how many people in team or org',
                'triggers'     => [
                    'how many', 'team size', 'member count', 'total member',
                    'total employee', 'people', 'colleague', 'headcount',
                    'how big', 'who is in', 'members in', 'how large',
                    'staff count', 'workforce',
                ],
            ],
            [
                'intent_name'  => 'my_manager',
                'display_name' => 'My Manager',
                'description'  => 'User asking who their manager is',
                'triggers'     => [
                    'manager', 'boss', 'supervisor', 'report to',
                    'who leads', 'my lead', 'my head', 'reporting to',
                    'who is above', 'who manages me', 'line manager',
                ],
            ],
            [
                'intent_name'  => 'my_reports',
                'display_name' => 'My Direct Reports',
                'description'  => 'Manager asking who reports to them',
                'triggers'     => [
                    'my report', 'who report', 'direct report', 'reportee',
                    'subordinate', 'who is under', 'my people', 'i manage',
                    'team member', 'under me', 'reports to me',
                ],
            ],
            [
                'intent_name'  => 'my_tasks',
                'display_name' => 'My Tasks',
                'description'  => 'User asking about their tasks',
                'triggers'     => [
                    'task', 'todo', 'work item', 'assignment', 'ticket',
                    'pending', 'assigned to me', 'what do i have', 'my job',
                    'my work', 'open task', 'current task', 'to do',
                ],
            ],
            [
                'intent_name'  => 'my_score',
                'display_name' => 'My Performance Score',
                'description'  => 'User asking about their performance',
                'triggers'     => [
                    'score', 'performance', 'rating', 'result', 'how am i',
                    'my kpi', 'evaluation', 'assessment', 'doing well',
                    'my points', 'increment score', 'performance score',
                    'how good', 'my stat',
                ],
            ],
            [
                'intent_name'  => 'logged_today',
                'display_name' => 'Did I Log Today',
                'description'  => 'User asking if they logged work today',
                'triggers'     => [
                    'did i log', 'my log', 'logged today', 'work today',
                    'log status', 'did i submit', 'my entry',
                    'have i logged', 'today log',
                ],
            ],
            [
                'intent_name'  => 'team_logged',
                'display_name' => 'Team Log Status',
                'description'  => 'Manager asking who logged today',
                'triggers'     => [
                    'who logged', 'not logged', 'missing log', 'team log',
                    'team status', 'who is active', 'how many logged',
                    'any missing', 'who is working', 'team activity',
                    'who did not log', 'absentee',
                ],
            ],
            [
                'intent_name'  => 'improve_score',
                'display_name' => 'Improve Score / Suggestions',
                'description'  => 'User asking for tips to improve',
                'triggers'     => [
                    'improve', 'suggestion', 'suggest', 'tip', 'advice',
                    'recommendation', 'recommend', 'better', 'boost',
                    'increase score', 'help me', 'guide', 'coach',
                    'focus on', 'what should i', 'how can i', 'get better',
                    'areas to improve', 'what to do',
                ],
            ],
            [
                'intent_name'  => 'blockers',
                'display_name' => 'My Blockers',
                'description'  => 'User asking about blockers',
                'triggers'     => [
                    'blocker', 'blocked', 'blocking', 'obstacle', 'stuck',
                    'impediment', 'dependency', 'waiting for',
                    'unresolved', 'escalated',
                ],
            ],
            [
                'intent_name'  => 'my_streak',
                'display_name' => 'My Streak',
                'description'  => 'User asking about their logging streak',
                'triggers'     => [
                    'streak', 'consecutive', 'days in a row', 'how long',
                    'logging streak', 'consistency', 'in a row',
                ],
            ],
            [
                'intent_name'  => 'sprint_status',
                'display_name' => 'Sprint Status',
                'description'  => 'User asking about current sprint',
                'triggers'     => [
                    'sprint', 'iteration', 'cycle', 'sprint progress',
                    'sprint deadline', 'sprint done', 'sprint status',
                    'current sprint', 'active sprint',
                ],
            ],
            [
                'intent_name'  => 'my_feedback',
                'display_name' => 'My Feedback',
                'description'  => 'User asking about their feedback',
                'triggers'     => [
                    'feedback', 'review', 'appraisal', 'quarterly',
                    'manager feedback', 'my review', 'performance feedback',
                    'how am i rated',
                ],
            ],
            [
                'intent_name'  => 'missed_notifs',
                'display_name' => 'What Did I Miss',
                'description'  => 'User asking what happened while away',
                'triggers'     => [
                    'miss', 'update', 'what happened', 'catch up', 'new',
                    'notification', 'news', 'while i was', 'fill me in',
                    'anything new', 'recent',
                ],
            ],
            [
                'intent_name'  => 'org_health',
                'display_name' => 'Org Health',
                'description'  => 'CEO/Admin asking about org status',
                'triggers'     => [
                    'org health', 'team health', 'how is team', 'org status',
                    'company status', 'overall', 'how are we', 'daily report',
                    'org report', 'platform status', 'health score',
                ],
            ],
            [
                'intent_name'  => 'onboarding',
                'display_name' => 'Getting Started',
                'description'  => 'New user asking how to get started',
                'triggers'     => [
                    'start', 'begin', 'how to use', 'get started', 'setup',
                    'first time', 'new here', 'onboard', 'tutorial',
                    'how does', 'explain', 'what is this', 'how to',
                    'where to',
                ],
            ],
        ];

        foreach ($intents as $intent) {
            AgentIntent::updateOrCreate(
                ['intent_name' => $intent['intent_name']],
                $intent
            );
        }
    }
}
