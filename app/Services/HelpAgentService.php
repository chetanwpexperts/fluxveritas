<?php
namespace App\Services;

class HelpAgentService
{
    private array $knowledge = [
        'dashboard' => [
            'what' => 'The Dashboard shows your GitHub contribution stats including commits, pull requests and activity chart.',
            'how' => 'Click Sync GitHub to load your latest data. Stats update every time you sync.',
            'tips' => [
                'Sync GitHub regularly to keep data fresh',
                'The chart shows last 30 days of activity',
                'Quick Actions let you navigate fast',
            ]
        ],
        'team' => [
            'what' => 'Team page shows all members in your organization with their GitHub stats and roles.',
            'how' => 'Click Invite Member to add someone. They get an invite link to join.',
            'tips' => [
                'Set GitHub usernames for accurate tracking',
                'Roles control what each member can see',
                'Remove members who have left the team',
            ]
        ],
        'fairness' => [
            'what' => 'The Fairness Engine uses AI to detect workload imbalance, bias and unfair task distribution in your team.',
            'how' => 'Click Run Analysis to check your team. The AI runs 5 verification layers before flagging anyone.',
            'tips' => [
                'Run analysis weekly for best results',
                'Always review context before confirming a flag',
                'Dismissed flags are logged for transparency',
            ]
        ],
        'blockers' => [
            'what' => 'Blockers tracks what is stopping your team from doing their work. Dependencies, approvals, meetings.',
            'how' => 'Click Report a Blocker to log what is blocking you. Set priority and assign to the right person.',
            'tips' => [
                'Resolve blockers quickly to keep team moving',
                'Use employee status for leaves and holidays',
                'Escalate critical blockers to leadership',
            ]
        ],
        'ai' => [
            'what' => 'AI Intelligence gives you executive summaries and lets you ask questions about your team in plain English.',
            'how' => 'Type any question in the Ask AI box. Try: Who contributed most this month?',
            'tips' => [
                'Upgrade to Pro for Ollama AI summaries',
                'Upgrade to Enterprise for GPT-4o',
                'Refresh summary to get latest insights',
            ]
        ],
        'projects' => [
            'what' => 'Projects connect to your GitHub repositories and track all activity for each repo separately.',
            'how' => 'Click New Project and enter your GitHub owner and repository name. Then sync to load data.',
            'tips' => [
                'Add all your active repos as projects',
                'Each project tracks its own commits and PRs',
                'View project details for deep insights',
            ]
        ],
        'ceo' => [
            'what' => 'Command Center is the leader view. See every team member output, flags and blockers in one place.',
            'how' => 'Click any row to expand and see recent activity, blockers and flags for that person.',
            'tips' => [
                'Sort by performance to spot top contributors',
                'Red rows need your immediate attention',
                'Use Quick Actions for common tasks',
            ]
        ],
        'admin' => [
            'what' => 'Admin Panel manages users, roles and permissions for your organization.',
            'how' => 'Go to User Management to change roles. Go to Modules to control which features are active.',
            'tips' => [
                'Owner role has full access',
                'Employee role has limited access',
                'Pending Approvals shows new registrations',
            ]
        ],
        'settings' => [
            'what' => 'Settings lets you manage your profile, organization, GitHub connection and notification preferences.',
            'how' => 'Use the sidebar to navigate between different settings sections.',
            'tips' => [
                'Set your GitHub username for tracking',
                'Enable notifications to stay informed',
                'Danger zone lets you delete the org',
            ]
        ],
        'worklog' => [
            'what' => 'Work Log lets you record everything you do during the day — meetings, development, calls, travel. Not just GitHub commits.',
            'how' => 'Click Log Activity to add an entry. Takes 30 seconds. Protects you from unfair reviews.',
            'tips' => [
                'Log throughout the day, not just at the end',
                'The Evidence field is your protection',
                'Your manager can see your daily work clearly',
            ]
        ],
        'departments' => [
            'what' => 'Departments organize your team by function. Each can have different tracking modes — GitHub, Manual, or Both.',
            'how' => 'Add departments and assign members. Set work mode based on how each team works.',
            'tips' => [
                'Non-tech teams use Manual mode',
                'Engineering uses GitHub or Hybrid',
                'Each department can have custom metrics',
            ]
        ],
        'notifications' => [
            'what' => 'Notifications are alerts from the autonomous AI agent watching your team 24/7.',
            'how' => 'Click the bell icon in the nav bar to see your latest alerts. Critical issues appear in red.',
            'tips' => [
                'Red dot means critical — act immediately',
                'The agent runs every hour automatically',
                'Mark read to keep your inbox clean',
            ]
        ],
    ];

    public function getPageKnowledge(string $page): array
    {
        return $this->knowledge[$page] ?? $this->knowledge['dashboard'];
    }

    public function getProactiveMessage(array $context): ?array
    {
        if (empty($context['github_username'])) {
            return [
                'type'    => 'suggestion',
                'message' => "I noticed you haven't set your GitHub username yet. This is needed to track your contributions.",
                'action'  => 'Set GitHub Username',
                'url'     => '/settings/github',
            ];
        }

        if (($context['team_count'] ?? 1) <= 1 && $context['page'] === 'dashboard') {
            return [
                'type'    => 'suggestion',
                'message' => "Your team only has you right now. Invite team members to start tracking everyone's output.",
                'action'  => 'Invite Team Member',
                'url'     => '/team/invite',
            ];
        }

        if (($context['last_synced'] ?? 'Never') === 'Never' && $context['page'] === 'dashboard') {
            return [
                'type'    => 'action',
                'message' => "Your GitHub data hasn't been synced yet. Sync now to see your real contribution stats.",
                'action'  => 'Sync GitHub Now',
                'url'     => null,
                'js'      => 'document.getElementById("sync-form").submit()',
            ];
        }

        if (($context['project_count'] ?? 0) === 0) {
            return [
                'type'    => 'suggestion',
                'message' => "No projects connected yet. Add your GitHub repository to start tracking commits and PRs.",
                'action'  => 'Add First Project',
                'url'     => '/projects/create',
            ];
        }

        if ($context['page'] === 'fairness' && ($context['flag_count'] ?? 0) === 0) {
            return [
                'type'    => 'info',
                'message' => "No fairness analysis has been run yet. Run your first analysis to check team equity.",
                'action'  => 'Run Analysis',
                'url'     => null,
            ];
        }

        if ($context['page'] === 'worklog' && ($context['today_logs'] ?? 0) === 0) {
            return [
                'type'    => 'action',
                'message' => "You haven't logged any work today. Quick — add what you've been working on. It only takes 30 seconds!",
                'action'  => 'Log Now',
                'url'     => '/work-log/today',
            ];
        }

        return null;
    }

    public function answerFromKnowledge(string $question): ?string
    {
        $q = strtolower($question);

        // These question types are handled by direct DB lookup in getDirectAnswer()
        // Return null immediately so they never get a stale KB answer
        $directTopics = [
            'who do i report', 'who is my manager', 'who is my boss',
            'my direct reports', 'who reports to me', 'my team members',
            'my tasks', 'my score', 'my performance', 'performance score',
            'did i log', 'my logs today', 'logged today',
            'what time', 'current time', 'what date', 'what day',
            'today\'s date', 'what is today', 'how am i doing',
        ];
        foreach ($directTopics as $topic) {
            if (str_contains($q, $topic)) {
                return null;
            }
        }

        // Data questions — let AI answer with real data
        $dataQuestions = [
            'how many', 'how much', 'count',
            'who is', 'who has', 'who hasn',
            'which', 'list', 'show me',
            'what is the status', 'are there',
            'do we have', 'is our', 'is the',
            'how is', 'what are the current',
            'today', 'this week', 'this month',
            'currently', 'right now', 'at the moment',
            'healthy', 'health', 'blocked',
            'open', 'pending', 'active',
            'performing', 'performance',
            'logged', 'not logged',
            // Hierarchy questions
            'report', 'manager', 'boss', 'hierarchy',
            'reports to', 'direct report', 'team lead',
            'who is my', 'org chart', 'reporting',
            'who do i', 'who should i', 'my manager',
        ];

        foreach ($dataQuestions as $dataQ) {
            if (str_contains($q, $dataQ)) {
                return null;
            }
        }

        if (str_contains($q, 'invite') || str_contains($q, 'add member') || str_contains($q, 'add team')) {
            return "To invite a team member:\n1. Click **Team** in the navigation\n2. Click **Invite Member** button\n3. Enter their email address\n4. Select their role (Employee/Admin)\n5. Click Send Invite\n6. Share the invite link with them";
        }

        if (str_contains($q, 'sync') || str_contains($q, 'github')) {
            return "To sync your GitHub data:\n1. Make sure your GitHub username is set in Settings → GitHub\n2. Make sure you have a Project with a GitHub repo connected\n3. Go to Dashboard\n4. Click the **Sync GitHub** button\n5. Wait for sync to complete";
        }

        if (str_contains($q, 'fairness') || str_contains($q, 'analysis') || str_contains($q, 'bias')) {
            return "To run a fairness analysis:\n1. Click **Fairness** in navigation\n2. Click **Run Analysis** button\n3. The AI checks workload balance, credit attribution and bias\n4. Review any flags that appear\n5. Confirm or dismiss each flag";
        }

        if (str_contains($q, 'project') || str_contains($q, 'repository') || str_contains($q, 'repo')) {
            return "To add a GitHub project:\n1. Click **Projects** in navigation\n2. Click **New Project**\n3. Enter project name\n4. Enter GitHub Owner (your GitHub username)\n5. Enter Repository name\n6. Click Create\n7. Go to Dashboard and Sync GitHub";
        }

        if (str_contains($q, 'role') || str_contains($q, 'permission') || str_contains($q, 'access')) {
            return "OutraqHQ has these roles:\n• **Owner** — full access to everything\n• **Admin** — manage team and settings\n• **Team Lead** — see team and fairness\n• **Employee** — see own data only\n• **Viewer** — read only access\n\nChange roles in Admin → User Management";
        }

        if (str_contains($q, 'blocker') || str_contains($q, 'blocked') || str_contains($q, 'stuck')) {
            return "To report a blocker:\n1. Click **Blockers** in navigation\n2. Click **Report a Blocker**\n3. Select blocker type\n4. Add title and description\n5. Set priority level\n6. Select who is blocking you\n7. Click Report Blocker";
        }

        if (str_contains($q, 'setting') || str_contains($q, 'profile') || str_contains($q, 'password')) {
            return "To access Settings:\n1. Click your name in top right\n2. Click **Settings** in dropdown\n3. Use the sidebar to navigate:\n   • Profile — update your details\n   • GitHub — connect your account\n   • Notifications — email preferences\n   • Organization — manage org settings";
        }

        if (str_contains($q, 'send email') || str_contains($q, 'email to')) {
            return "I can send emails for you! Just tell me:\n• 'Send email to CEO about [topic]'\n• 'Send email to Sarah about [topic]'\n• 'Send email to manager about [topic]'\n• 'Send email to team about [topic]'\n\nI will write the email and send it automatically!";
        }

        return null;
    }

    public function getWelcomeMessage(string $page, string $role): string
    {
        $messages = [
            'dashboard' => 'Welcome to your Dashboard! This shows your GitHub contribution intelligence. Sync GitHub to see your real data.',
            'team'      => 'This is your Team page. You can see all members, their roles and contribution stats here.',
            'fairness'  => 'This is the Fairness Engine. It uses AI to detect bias and workload imbalance in your team.',
            'blockers'  => 'This is the Blockers tracker. Report anything stopping your work and track team dependencies here.',
            'ai'        => 'This is AI Intelligence. Ask me anything about your team in plain English!',
            'ceo'       => 'Welcome to Command Center. This is your full team truth view — every metric, no filters.',
        ];

        return $messages[$page] ?? 'How can I help you today?';
    }
}
