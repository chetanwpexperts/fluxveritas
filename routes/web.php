<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\CeoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DependencyController;
use App\Http\Controllers\FairnessController;
use App\Http\Controllers\HelpAgentController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamManagementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\IncrementController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\OrgChartController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\HrManagementController;
use App\Http\Controllers\EmployeeDirectoryController;
use App\Http\Controllers\HrOnboardingController;
use App\Http\Controllers\HrReportsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\PeerFeedbackController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MagicActionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('home');

Route::get('/tour', function () {
    return view('tour');
})->name('tour');

Route::get('/docs', function () {
    return view('docs');
})->name('docs');

Route::get('/contact',  [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

Route::get('/refund-policy', function () {
    return view('refund-policy');
})->name('refund-policy');

// ─── 1-Click Magic Action Execution (Public signed link) ──────────────────────
Route::get('/api/action/execute', [MagicActionController::class, 'execute'])->name('action.execute');

// ─── Cryptographic Fair Workplace Badge Public Verification ───────────────────
Route::get('/verify-fairness/{token}', [CertificateController::class, 'verify'])->name('fairness.verify');
Route::get('/org/{orgId}/fairness-badge', [CertificateController::class, 'badge'])->name('fairness.badge');

// ─── Peer Feedback — public (no login required) ───────────────────────────────
Route::get('/peer-feedback/{token}', [PeerFeedbackController::class, 'showForm'])
    ->name('peer-feedback.form');
Route::post('/peer-feedback/{token}', [PeerFeedbackController::class, 'submitForm'])
    ->name('peer-feedback.submit');

// ─── Dashboard (auth + onboarding check) ─────────────────────────────────────
Route::middleware(['auth', 'check.onboarding'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('/dashboard/sync-github', [DashboardController::class, 'syncGitHub'])
        ->middleware(['permission:sync_github', 'module:github_sync'])
        ->name('dashboard.sync-github');
});

// ─── Onboarding holding pages (auth only, no onboarding check) ───────────────
Route::middleware('auth')->group(function () {
    Route::get('/pending-approval', [OnboardingController::class, 'pending'])->name('pending-approval');
    Route::post('/pending-approval/activate', [OnboardingController::class, 'activateWithToken'])->name('onboarding.activate');
});

// ─── All other authenticated routes (auth + onboarding check) ────────────────
Route::middleware(['auth', 'check.onboarding'])->group(function () {

    // Organization
    Route::middleware('permission:manage_organization')->group(function () {
        Route::get('/organization/create', [OrganizationController::class, 'create'])->name('organization.create');
        Route::post('/organization', [OrganizationController::class, 'store'])->name('organization.store');
    });

    // Projects
    Route::middleware('permission:view_projects')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{id}', [ProjectController::class, 'show'])->name('projects.show');
    });

    Route::middleware('permission:create_projects')->group(function () {
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    });

    Route::middleware('permission:edit_projects')->group(function () {
        Route::get('/projects/{id}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::patch('/projects/{id}', [ProjectController::class, 'update'])->name('projects.update');
    });

    Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
        ->middleware('permission:delete_projects')
        ->name('projects.destroy');

    // Team
    Route::middleware('permission:view_team')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    });

    Route::middleware('permission:invite_members')->group(function () {
        Route::get('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
        Route::post('/team/invite', [TeamController::class, 'sendInvite'])->name('team.sendInvite');
        Route::get('/team/bulk-invite', [TeamController::class, 'bulkInvite'])->name('team.bulk-invite');
        Route::post('/team/bulk-invite', [TeamController::class, 'sendBulkInvite'])->name('team.sendBulkInvite');
    });

    Route::delete('/team/members/{userId}', [TeamController::class, 'removeUser'])
        ->middleware('permission:remove_members')
        ->name('team.remove');

    Route::patch('/team/members/{userId}/role', [TeamController::class, 'updateRole'])
        ->middleware('permission:update_member_roles')
        ->name('team.updateRole');

    // Org Chart
    Route::get('/org-chart', [OrgChartController::class, 'index'])->name('org-chart.index');
    Route::get('/org-chart/reports/{userId}', [OrgChartController::class, 'directReports'])->name('org-chart.reports');
    Route::patch('/org-chart/manager/{userId}', [OrgChartController::class, 'updateManager'])
        ->middleware('role:admin|super_admin|owner')
        ->name('org-chart.update-manager');

    // Blockers & Dependencies
    Route::middleware(['permission:view_blockers', 'module:blockers'])->group(function () {
        Route::get('/dependencies', [DependencyController::class, 'index'])->name('dependency.index');
    });

    Route::middleware('permission:create_blockers')->group(function () {
        Route::post('/dependencies/blocker', [DependencyController::class, 'reportBlocker'])->name('dependency.reportBlocker');
    });

    Route::middleware('permission:resolve_blockers')->group(function () {
        Route::post('/dependencies/blocker/{id}/resolve', [DependencyController::class, 'resolveBlocker'])->name('dependency.resolve');
    });

    Route::middleware('permission:escalate_blockers')->group(function () {
        Route::post('/dependencies/blocker/{id}/escalate', [DependencyController::class, 'escalateBlocker'])->name('dependency.escalate');
    });

    Route::post('/dependencies/blocker/{id}/dispute', [DependencyController::class, 'raiseDispute'])->name('dependency.dispute');
    Route::post('/dependencies/blocker/{id}/acknowledge', [DependencyController::class, 'acknowledgeBlocker'])->name('dependency.acknowledge');
    Route::post('/dependencies/blocker/{id}/comment', [DependencyController::class, 'commentOnBlocker'])->name('dependency.comment');
    Route::get('/dependencies/blocker/{id}', [DependencyController::class, 'showBlocker'])->name('dependency.show');
    Route::get('/dependencies/dispute-patterns', [DependencyController::class, 'disputePatterns'])->name('dependency.patterns');

    Route::middleware('permission:manage_employee_status')->group(function () {
        Route::post('/dependencies/status', [DependencyController::class, 'setEmployeeStatus'])->name('dependency.setStatus');
        Route::post('/dependencies/status/{userId}/clear', [DependencyController::class, 'clearEmployeeStatus'])->name('dependency.clearStatus');
    });

    // Fairness
    Route::middleware(['permission:view_fairness', 'module:fairness_engine'])->group(function () {
        Route::get('/fairness', [FairnessController::class, 'index'])->name('fairness.index');
    });

    Route::middleware(['permission:run_fairness_analysis', 'module:fairness_engine'])->group(function () {
        Route::post('/fairness/run', [FairnessController::class, 'runAnalysis'])->name('fairness.run');
    });

    Route::middleware(['permission:confirm_flags', 'module:fairness_engine'])->group(function () {
        Route::post('/fairness/flags/{id}/confirm', [FairnessController::class, 'confirmFlag'])->name('fairness.confirm');
    });

    Route::middleware(['permission:dismiss_flags', 'module:fairness_engine'])->group(function () {
        Route::post('/fairness/flags/{id}/dismiss', [FairnessController::class, 'dismissFlag'])->name('fairness.dismiss');
    });

    // AI
    Route::middleware(['permission:view_ai', 'module:ai_intelligence'])->group(function () {
        Route::get('/ai', [AiController::class, 'dashboard'])->name('ai.dashboard');
    });

    Route::middleware(['permission:query_ai', 'module:ai_intelligence'])->group(function () {
        Route::post('/ai/query', [AiController::class, 'query'])->name('ai.query');
    });

    Route::middleware(['permission:refresh_ai_summary', 'module:ai_intelligence'])->group(function () {
        Route::post('/ai/refresh', [AiController::class, 'refresh'])->name('ai.refresh');
    });

    // Command Center
    Route::get('/ceo', [CeoController::class, 'index'])
        ->middleware(['role:ceo|super_admin', 'module:command_center'])
        ->name('ceo.index');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::get('/organization', [SettingsController::class, 'organization'])->name('organization');
        Route::post('/organization', [SettingsController::class, 'updateOrganization'])->name('organization.update');
        Route::get('/github', [SettingsController::class, 'github'])->name('github');
        Route::post('/github', [SettingsController::class, 'updateGithub'])->name('github.update');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::post('/organization/delete', [SettingsController::class, 'deleteOrganization'])->name('organization.delete');
        Route::get('/platform',  [SettingsController::class, 'platform'])->name('platform');
        Route::get('/ai',        [SettingsController::class, 'ai'])->name('ai');
        Route::post('/ai/intents/{id}/triggers',   [SettingsController::class, 'addIntentTrigger'])->name('ai.intent.add-trigger')->middleware('role:super_admin');
        Route::delete('/ai/intents/{id}/triggers', [SettingsController::class, 'removeIntentTrigger'])->name('ai.intent.remove-trigger')->middleware('role:super_admin');
        Route::get('/security',  [SettingsController::class, 'security'])->name('security');
        Route::get('/audit',     [SettingsController::class, 'audit'])->name('audit');
    });

    // Profile (any authenticated user)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/github', [ProfileController::class, 'editGithub'])->name('profile.github');
    Route::patch('/profile/github', [ProfileController::class, 'updateGithub'])->name('profile.github.update');

    // Help Agent
    Route::post('/help-agent/ask', [HelpAgentController::class, 'ask'])->name('help.ask');
    Route::get('/help-agent/context', [HelpAgentController::class, 'context'])->name('help.context');

    // Mini ERP Routes
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('/expenses/{id}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');

    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');

    Route::get('/timesheets', [TimesheetController::class, 'index'])->name('timesheets.index');
    Route::post('/timesheets/clock-in', [TimesheetController::class, 'clockIn'])->name('timesheets.clock-in');
    Route::post('/timesheets/clock-out', [TimesheetController::class, 'clockOut'])->name('timesheets.clock-out');

    // Departments
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentController::class, 'create'])->name('create');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::post('/assign', [DepartmentController::class, 'assignMember'])->name('assign');
        Route::get('/{id}', [DepartmentController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{id}', [DepartmentController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/members/{userId}', [DepartmentController::class, 'removeMember'])->name('remove-member');
    });

    // Work Logs
    Route::prefix('work-log')->name('worklog.')->group(function () {
        Route::get('/team',      [WorkLogController::class, 'team'])->name('team');
        Route::get('/today',     [WorkLogController::class, 'todayLog'])->name('today');
        Route::get('/',          [WorkLogController::class, 'index'])->name('index');
        Route::post('/',         [WorkLogController::class, 'store'])->name('store');
        Route::post('/summary',  [WorkLogController::class, 'generateDailySummary'])->name('summary');
        Route::post('/metric',   [WorkLogController::class, 'addMetricEntry'])->name('metric');
        // Quick Log (AI-powered)
        Route::post('/quick',    [WorkLogController::class, 'quickLog'])->name('quick');
        // Bulk Store (Weekly Grid)
        Route::post('/bulk',     [WorkLogController::class, 'bulkStore'])->name('bulk');
        // Week logs for grid navigation
        Route::get('/week-logs', [WorkLogController::class, 'getWeekLogs'])->name('week-logs');
        // Copy yesterday
        Route::get('/yesterday', [WorkLogController::class, 'getYesterdayLog'])->name('yesterday');
        // Templates
        Route::get('/templates',          [WorkLogController::class, 'getTemplates'])->name('templates.index');
        Route::post('/templates',         [WorkLogController::class, 'saveTemplate'])->name('templates.store');
        Route::delete('/templates/{id}',  [WorkLogController::class, 'deleteTemplate'])->name('templates.destroy');
        Route::post('/templates/{id}/use',[WorkLogController::class, 'useTemplate'])->name('templates.use');
        // User's active tasks for task-linking dropdown
        Route::get('/user-tasks', [WorkLogController::class, 'getUserTasks'])->name('user-tasks');
        // Update / Delete by ID (keep last to avoid route conflicts)
        Route::patch('/{id}',    [WorkLogController::class, 'update'])->name('update');
        Route::delete('/{id}',   [WorkLogController::class, 'destroy'])->name('destroy');
    });

    // Tasks
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/my', [TaskController::class, 'myTasks'])->name('my');
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{id}', [TaskController::class, 'show'])->name('show');
        Route::patch('/{id}/status', [TaskController::class, 'updateStatus'])->name('status');
        Route::patch('/{id}', [TaskController::class, 'update'])->name('update');
        Route::post('/{id}/comment', [TaskController::class, 'comment'])->name('comment');
        Route::delete('/{id}', [TaskController::class, 'destroy'])->name('destroy');
    });

    // Sprints
    Route::prefix('sprints')->name('sprints.')->group(function () {
        Route::get('/', [SprintController::class, 'index'])->name('index');
        Route::get('/create', [SprintController::class, 'create'])->name('create');
        Route::post('/', [SprintController::class, 'store'])->name('store');
        Route::get('/{id}', [SprintController::class, 'show'])->name('show');
        Route::post('/{id}/start', [SprintController::class, 'start'])->name('start');
        Route::post('/{id}/complete', [SprintController::class, 'complete'])->name('complete');
        Route::post('/{id}/tasks', [SprintController::class, 'addTask'])->name('add-task');
        Route::delete('/{id}/tasks/{taskId}', [SprintController::class, 'removeTask'])->name('remove-task');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/count', [NotificationController::class, 'unreadCount'])->name('count');
        Route::get('/latest', [NotificationController::class, 'getLatest'])->name('latest');
        Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/{id}/dismiss', [NotificationController::class, 'dismiss'])->name('dismiss');
    });

    // Increment Management
    Route::prefix('increment')->name('increment.')->middleware('module:increment_calculator')->group(function () {
        Route::get('/settings',         [IncrementController::class, 'settings'])->name('settings');
        Route::post('/settings',        [IncrementController::class, 'savePolicy'])->name('save-policy');
        Route::get('/reviews',          [IncrementController::class, 'reviewDashboard'])->name('reviews');
        Route::post('/calculate',       [IncrementController::class, 'calculate'])->name('calculate');
        Route::post('/calculate-month', [IncrementController::class, 'calculateMonth'])->name('calculate-month');
        Route::post('/reviews/{id}/manager', [IncrementController::class, 'managerReview'])->name('manager-review');
        Route::post('/reviews/{id}/approve', [IncrementController::class, 'ceoApprove'])->name('approve');
        Route::get('/my',               [IncrementController::class, 'myIncrement'])->name('my');
        Route::post('/appeal/{reviewId}',[IncrementController::class, 'submitAppeal'])->name('appeal');
    });

    // Feedback System
    Route::prefix('feedback')->name('feedback.')->group(function () {
        // Manager routes
        Route::get('/', [FeedbackController::class, 'index'])
            ->middleware('role:admin|manager|team_lead|owner')
            ->name('index');
        Route::get('/create/{employeeId}', [FeedbackController::class, 'create'])
            ->middleware('role:admin|manager|team_lead|owner')
            ->name('create');
        Route::post('/', [FeedbackController::class, 'store'])
            ->middleware('role:admin|manager|team_lead|owner')
            ->name('store');
        // Employee routes (any auth user)
        Route::get('/my', [FeedbackController::class, 'myFeedback'])->name('my');
        Route::get('/my/{feedbackId}', [FeedbackController::class, 'show'])->name('show');
        Route::post('/{feedbackId}/statement', [FeedbackController::class, 'storeStatement'])->name('statement');
        // Admin/CEO routes
        Route::get('/admin', [FeedbackController::class, 'adminIndex'])
            ->middleware('role:admin|owner|ceo|super_admin')
            ->name('admin');
        Route::get('/admin/{feedbackId}', [FeedbackController::class, 'adminShow'])
            ->middleware('role:admin|owner|ceo|super_admin')
            ->name('admin.show');
        // Peer feedback routes
        Route::post('/{employeeId}/request-peers', [PeerFeedbackController::class, 'requestPeerFeedback'])
            ->middleware(['role:admin|manager|team_lead|owner|ceo', 'module:peer_feedback'])
            ->name('request-peers');
        Route::get('/bias-reports', [PeerFeedbackController::class, 'biasReports'])
            ->middleware(['role:admin|owner|ceo|super_admin', 'module:peer_feedback'])
            ->name('bias-reports');
    });

    // Admin Panel
    Route::prefix('admin')->name('admin.')->middleware('permission:manage_roles')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::patch('/users/{userId}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
        Route::patch('/users/{userId}/toggle', [AdminController::class, 'toggleUserStatus'])->name('users.toggle');
        Route::get('/roles', [AdminController::class, 'roles'])->name('roles');
        Route::get('/permissions', [AdminController::class, 'permissions'])->name('permissions');
        Route::post('/users/{userId}/permissions/give', [AdminController::class, 'givePermission'])->name('users.give-permission');
        Route::post('/users/{userId}/permissions/revoke', [AdminController::class, 'revokePermission'])->name('users.revoke-permission');
        Route::post('/users/{id}/permissions/grant', [AdminController::class, 'givePermission'])->name('permissions.grant');
        Route::delete('/users/{id}/permissions/revoke', [AdminController::class, 'revokePermission'])->name('permissions.revoke');
        Route::get('/pending-users', [AdminController::class, 'pendingUsers'])->name('pending');
        Route::post('/users/{userId}/approve', [AdminController::class, 'approveUser'])->name('approve');
        Route::post('/users/{userId}/reject', [AdminController::class, 'rejectUser'])->name('reject');
        // Module management
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules');
        Route::post('/modules/{moduleName}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');

        // User activation / deactivation (within org)
        Route::patch('/users/{id}/deactivate', [AdminController::class, 'deactivateUser'])->name('users.deactivate');
        Route::patch('/users/{id}/activate',   [AdminController::class, 'activateUser'])->name('users.activate');

        // User CRUD
        Route::get('/users/create',     [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users',           [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{id}/edit',  [AdminController::class, 'editUser'])->name('users.edit');
        Route::patch('/users/{id}',     [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}',    [AdminController::class, 'deleteUser'])->name('users.delete');

        // Bulk Manager Assign
        Route::post('/users/bulk-manager', [AdminController::class, 'bulkAssignManager'])->name('users.bulk-manager');

        // Designation Management
        Route::get('/designations',             [AdminController::class, 'designations'])->name('designations');
        Route::get('/designations/create',      [AdminController::class, 'createDesignation'])->name('designations.create');
        Route::post('/designations',            [AdminController::class, 'storeDesignation'])->name('designations.store');
        Route::get('/designations/{id}/edit',   [AdminController::class, 'editDesignation'])->name('designations.edit');
        Route::patch('/designations/{id}',      [AdminController::class, 'updateDesignation'])->name('designations.update');
        Route::delete('/designations/{id}',     [AdminController::class, 'deleteDesignation'])->name('designations.delete');
    });
});

// ─── Super Admin Panel ────────────────────────────────────────────────────────
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'super_admin', 'superadmin.audit'])->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('index');
    Route::get('/organizations', [SuperAdminController::class, 'organizations'])->name('organizations');
    Route::get('/organizations/{id}', [SuperAdminController::class, 'showOrganization'])->name('organizations.show');
    Route::post('/organizations/{id}/approve', [SuperAdminController::class, 'approveOrganization'])->name('organizations.approve');
    Route::post('/organizations/{id}/suspend', [SuperAdminController::class, 'suspendOrganization'])->name('organizations.suspend');
    Route::post('/organizations/{id}/reactivate', [SuperAdminController::class, 'reactivateOrganization'])->name('organizations.reactivate');
    Route::post('/organizations/{id}/plan', [SuperAdminController::class, 'changePlan'])->name('organizations.plan');
    Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
    Route::post('/users/{id}/impersonate', [SuperAdminController::class, 'impersonate'])->name('impersonate');
    Route::get('/stop', [SuperAdminController::class, 'stopImpersonate'])->name('stop');
    Route::post('/switch/{orgId}', [SuperAdminController::class, 'switchOrganization'])->name('switch');
    Route::get('/switch-back', [SuperAdminController::class, 'switchBack'])->name('switchBack');
    // Org module management
    Route::get('/organizations/{id}/modules', [SuperAdminController::class, 'orgModules'])->name('org.modules');
    Route::post('/organizations/{id}/modules/{moduleName}/toggle', [SuperAdminController::class, 'toggleOrgModule'])->name('org.modules.toggle');

    // ── User management (AJAX) ──
    Route::patch('/users/{id}/suspend',     [SuperAdminController::class, 'suspendUser'])->name('users.suspend');
    Route::patch('/users/{id}/activate',    [SuperAdminController::class, 'activateUser'])->name('users.activate');
    Route::patch('/users/{id}/edit',        [SuperAdminController::class, 'editUser'])->name('users.edit');
    Route::delete('/users/{id}',            [SuperAdminController::class, 'deleteUser'])->name('users.delete');
    Route::patch('/users/{id}/change-role', [SuperAdminController::class, 'changeRole'])->name('users.change-role');

    // ── Organization management (AJAX) ──
    Route::patch('/organizations/{id}/suspend',  [SuperAdminController::class, 'suspendOrg'])->name('organizations.suspend-ajax');
    Route::patch('/organizations/{id}/activate', [SuperAdminController::class, 'activateOrg'])->name('organizations.activate');
    Route::delete('/organizations/{id}',         [SuperAdminController::class, 'deleteOrg'])->name('organizations.delete');

    // ── Platform Designation management ──
    Route::get('/designations',         [SuperAdminController::class, 'designations'])->name('designations');
    Route::post('/designations',        [SuperAdminController::class, 'storeDesignation'])->name('designations.store');
    Route::delete('/designations/{id}', [SuperAdminController::class, 'deleteDesignation'])->name('designations.delete');
});

// ─── Announcements ───────────────────────────────────────────────────────────
Route::prefix('announcements')->name('announcements.')->middleware(['auth'])->group(function () {
    Route::get('/',           [AnnouncementController::class, 'index'])->name('index');
    Route::post('/',          [AnnouncementController::class, 'store'])->name('store');
    Route::delete('/{id}',    [AnnouncementController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/read', [AnnouncementController::class, 'markRead'])->name('read');
    Route::post('/read-all',  [AnnouncementController::class, 'markAllRead'])->name('read-all');
    Route::post('/{id}/pin',  [AnnouncementController::class, 'togglePin'])->name('pin');
    Route::get('/unread-count', [AnnouncementController::class, 'unreadCount'])->name('unread-count');
});

// ─── Leaves ──────────────────────────────────────────────────────────────────
Route::prefix('leaves')->name('leaves.')->middleware(['auth'])->group(function () {
    Route::get('/',                            [LeaveController::class, 'index'])->name('index');
    Route::post('/apply',                      [LeaveController::class, 'apply'])->name('apply');
    Route::post('/{id}/approve',               [LeaveController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject',                [LeaveController::class, 'reject'])->name('reject');
    Route::post('/{id}/cancel',                [LeaveController::class, 'cancel'])->name('cancel');
    Route::get('/settings',                    [LeaveController::class, 'settings'])->name('settings')->middleware('role:hr|admin|manager|owner|ceo|super_admin');
    Route::post('/settings/types',             [LeaveController::class, 'storeLeaveType'])->name('types.store')->middleware('role:hr|admin|manager|owner|ceo|super_admin');
    Route::patch('/settings/types/{id}',       [LeaveController::class, 'updateLeaveType'])->name('types.update')->middleware('role:hr|admin|manager|owner|ceo|super_admin');
    Route::post('/settings/allocate-all',      [LeaveController::class, 'allocateAll'])->name('allocate-all')->middleware('role:hr|admin|manager|owner|ceo|super_admin');
    Route::get('/hr',                          [LeaveController::class, 'hrIndex'])->name('hr')->middleware('role:hr|admin|manager|owner|ceo|super_admin');
    Route::get('/export',                      [LeaveController::class, 'exportCsv'])->name('export')->middleware('role:hr|admin|manager|owner|ceo|super_admin');
});

// ─── HR Management ───────────────────────────────────────────────────────────
Route::prefix('hr-management')->name('hr.')->middleware(['auth'])->group(function () {
    Route::get('/',        [HrManagementController::class, 'index'])->name('index');
    Route::post('/assign', [HrManagementController::class, 'assignHr'])->name('assign');
    Route::post('/revoke', [HrManagementController::class, 'revokeHr'])->name('revoke');
});

// ─── Employee Directory ───────────────────────────────────────────────────────
Route::prefix('directory')->name('directory.')->middleware(['auth'])->group(function () {
    Route::get('/',                           [EmployeeDirectoryController::class, 'index'])->name('index');
    Route::get('/me/edit',                    [EmployeeDirectoryController::class, 'editProfile'])->name('edit');
    Route::post('/me/update',                 [EmployeeDirectoryController::class, 'updateProfile'])->name('update');
    Route::get('/{user}/hr-edit',             [EmployeeDirectoryController::class, 'hrEdit'])->name('hr-edit');
    Route::post('/{user}/hr-update',          [EmployeeDirectoryController::class, 'hrUpdate'])->name('hr-update');
    Route::get('/{user}',                     [EmployeeDirectoryController::class, 'show'])->name('show');
});

// ─── Document Center ──────────────────────────────────────────────────────────
Route::prefix('documents')->name('documents.')->middleware(['auth'])->group(function () {
    Route::get('/',                    [DocumentController::class, 'index'])->name('index');
    Route::get('/upload',              [DocumentController::class, 'create'])->name('create');
    Route::post('/',                   [DocumentController::class, 'store'])->name('store');
    Route::get('/{document}',          [DocumentController::class, 'show'])->name('show');
    Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
    Route::post('/{document}/version', [DocumentController::class, 'updateVersion'])->name('version');
    Route::delete('/{document}',       [DocumentController::class, 'destroy'])->name('destroy');
});

// ─── Global Search ────────────────────────────────────────────────────────────
Route::get('/search', [GlobalSearchController::class, 'search'])->name('search')->middleware('auth');

// ─── HR Onboarding ────────────────────────────────────────────────────────────
Route::prefix('hr-onboarding')->name('hr-onboarding.')->middleware(['auth'])->group(function () {
    Route::get('/',                               [HrOnboardingController::class, 'index'])->name('index');
    Route::get('/create',                         [HrOnboardingController::class, 'create'])->name('create');
    Route::post('/',                              [HrOnboardingController::class, 'store'])->name('store');
    Route::get('/{checklist}',                    [HrOnboardingController::class, 'show'])->name('show');
    Route::post('/{checklist}/tasks',             [HrOnboardingController::class, 'addTask'])->name('tasks.add');
    Route::post('/tasks/{task}/complete',         [HrOnboardingController::class, 'completeTask'])->name('tasks.complete');
});

// ─── HR Reports ───────────────────────────────────────────────────────────────
Route::get('/hr/reports', [HrReportsController::class, 'index'])
    ->middleware(['auth', 'module:hr_reports'])
    ->name('hr.reports');

// ─── Teams (project teams within org) ────────────────────────────────────────
Route::prefix('teams')->name('teams.')->middleware(['auth'])->group(function () {
    Route::get('/',                                        [TeamManagementController::class, 'index'])->name('index');
    Route::get('/create',                                  [TeamManagementController::class, 'create'])->name('create');
    Route::post('/',                                       [TeamManagementController::class, 'store'])->name('store');
    Route::get('/{id}',                                    [TeamManagementController::class, 'show'])->name('show');
    Route::get('/{id}/edit',                               [TeamManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}',                                    [TeamManagementController::class, 'update'])->name('update');
    Route::delete('/{id}',                                 [TeamManagementController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/members',                           [TeamManagementController::class, 'addMember'])->name('members.add');
    Route::delete('/{teamId}/members/{userId}',            [TeamManagementController::class, 'removeMember'])->name('members.remove');
    Route::get('/{id}/available-members',                  [TeamManagementController::class, 'availableMembers'])->name('available-members');
});

// ─── Public routes ────────────────────────────────────────────────────────────
Route::get('/team/accept/{token}', [TeamController::class, 'acceptInvite'])->name('team.accept');

// ─── Reports ─────────────────────────────────────────────────────────────────
Route::prefix('reports')->name('reports.')->middleware(['auth', 'module:reports'])->group(function () {
    Route::get('/ceo', [ReportController::class, 'ceoDashboard'])
        ->name('ceo')
        ->middleware('role:ceo|super_admin');
    Route::post('/ceo/send-now', [ReportController::class, 'sendCeoDigestNow'])
        ->name('ceo.send-now')
        ->middleware('role:admin|owner|ceo|super_admin');
    Route::get('/my', [ReportController::class, 'employeeReport'])->name('my');
    Route::get('/employee/{userId}', [ReportController::class, 'employeeReport'])
        ->name('employee')
        ->middleware('role:admin|manager|owner|ceo|super_admin');
    Route::get('/team', [ReportController::class, 'teamReports'])
        ->name('team')
        ->middleware('role:admin|manager|owner|ceo|super_admin');
});

// ─── Employee Import ──────────────────────────────────────────────────────────
Route::prefix('import')->name('import.')->middleware(['auth', 'check.onboarding'])->group(function () {
    Route::get('/employees',           [EmployeeImportController::class, 'index'])->name('employees');
    Route::get('/employees/template',  [EmployeeImportController::class, 'template'])->name('employees.template');
    Route::post('/employees/preview',  [EmployeeImportController::class, 'preview'])->name('employees.preview');
    Route::post('/employees/run',      [EmployeeImportController::class, 'run'])->name('employees.run');
});

// ─── Billing ─────────────────────────────────────────────────────────────────
Route::prefix('billing')->name('billing.')->middleware(['auth', 'check.onboarding'])->group(function () {
    Route::get('/',                           [BillingController::class, 'index'])->name('index');
    Route::post('/order',                     [BillingController::class, 'createOrder'])->name('order');
    Route::post('/verify',                    [BillingController::class, 'verify'])->name('verify');
    Route::post('/failed',                    [BillingController::class, 'failed'])->name('failed');
    Route::get('/receipts/{payment}',         [BillingController::class, 'receipt'])->whereNumber('payment')->name('receipt');
    Route::post('/payments/{payment}/refund', [BillingController::class, 'refund'])->whereNumber('payment')->name('refund');
    Route::post('/downgrade',                 [BillingController::class, 'downgrade'])->name('downgrade');
    Route::post('/downgrade/cancel',          [BillingController::class, 'cancelDowngrade'])->name('downgrade.cancel');
});

// Razorpay server-to-server events (signature-verified, CSRF-exempt in bootstrap/app.php)
Route::post('/billing/webhook', [BillingController::class, 'webhook'])->name('billing.webhook');

// ─── System Guardian ─────────────────────────────────────────────────────────
Route::prefix('agent')->name('agent.')->middleware(['auth', 'super_admin', 'superadmin.audit'])->group(function () {
    Route::get('/health',        [AgentController::class, 'health'])->name('health');
    Route::post('/run',          [AgentController::class, 'runNow'])->name('run');
    Route::post('/cache/clear',  [AgentController::class, 'clearCache'])->name('cache.clear');
    Route::post('/logs/clear',   [AgentController::class, 'clearLogs'])->name('logs.clear');
    Route::post('/optimize',     [AgentController::class, 'optimizeApp'])->name('optimize');
});

require __DIR__.'/auth.php';
