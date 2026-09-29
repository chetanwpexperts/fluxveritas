<?php
namespace App\Http\Controllers;

use App\Services\GitHub\GitHubApi;
use App\Services\GitHub\GitHubApiException;
use App\Services\GitHub\GitHubSyncService;
use App\Models\AgentIntent;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\IntentMatcherService;
use App\Services\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $user = auth()->user();
        $org  = $user->organization;
        return view('settings.index', compact('user', 'org'));
    }

    public function organization()
    {
        $this->authorize('edit_org_settings');
        $user = auth()->user();
        $org  = $user->organization;

        $moduleService = new ModuleService();
        $modules = $moduleService->getOrgModules($org->id);

        return view('settings.organization', compact('org', 'modules'));
    }

    public function updateOrganization(Request $request)
    {
        $this->authorize('edit_org_settings');

        $request->validate([
            'name' => 'required|min:2|max:100',
            'slug' => ['required', 'min:2', 'max:50', 'regex:/^[a-z0-9\-]+$/'],
        ]);

        $org = auth()->user()->organization;

        $exists = Organization::where('slug', $request->slug)
            ->where('id', '!=', $org->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['slug' => 'This slug is already taken.']);
        }

        $org->update([
            'name' => $request->name,
            'slug' => $request->slug,
        ]);

        return back()->with('success', 'Organization settings saved.');
    }

    public function github()
    {
        $this->authorize('manage_github_settings');
        $user = auth()->user();
        $org  = $user->organization;

        $projects = Project::where('organization_id', $org->id)
            ->whereNotNull('github_owner')
            ->whereNotNull('github_repo')
            ->get();

        $tokenSaved  = isset($org->settings['github_token']);
        $tokenSource = app(GitHubSyncService::class)->tokenFor($org)['source'];
        $githubLogin = $org->settings['github_token_login'] ?? null;
        $lastSync    = $org->settings['github_last_sync'] ?? null;

        return view('settings.github', compact('org', 'user', 'projects', 'tokenSaved', 'tokenSource', 'githubLogin', 'lastSync'));
    }

    /** Save (after checking it with GitHub) or remove the organization's GitHub token. */
    public function updateGithubToken(Request $request)
    {
        $this->authorize('manage_github_settings');
        $org      = auth()->user()->organization;
        $settings = $org->settings ?? [];

        if ($request->input('action') === 'remove') {
            unset($settings['github_token'], $settings['github_token_login']);
            $org->update(['settings' => $settings]);
            $this->auditGithub('github.token_removed', null);

            return back()->with('success', 'GitHub token removed. Only public repositories can be synced now.');
        }

        $request->validate(['github_token' => ['required', 'string', 'min:20', 'max:255']]);

        try {
            $login = (new GitHubApi(trim($request->github_token)))->user()['login'] ?? null;
        } catch (GitHubApiException $e) {
            return back()->withErrors(['github_token' => $e->kind === 'auth'
                ? 'GitHub rejected this token. Check it has not expired and try again.'
                : $e->getMessage()]);
        }

        $settings['github_token']       = encrypt(trim($request->github_token));
        $settings['github_token_login'] = $login;
        $org->update(['settings' => $settings]);
        $this->auditGithub('github.token_updated', $login);

        return back()->with('success', "GitHub connected as {$login}. Private repositories this account can read will now sync.");
    }

    private function auditGithub(string $action, ?string $login): void
    {
        \App\Models\AuditLog::create([
            'organization_id' => auth()->user()->organization_id,
            'user_id'         => auth()->id(),
            'action'          => $action,
            'entity_type'     => 'organization',
            'entity_id'       => auth()->user()->organization_id,
            'new_values'      => ['github_login' => $login],
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
        ]);
    }

    public function updateGithub(Request $request)
    {
        $this->authorize('manage_github_settings');

        $request->validate([
            'github_username' => ['nullable', 'max:39', 'regex:/^[a-zA-Z0-9\-]*$/'],
        ]);

        auth()->user()->update(['github_username' => $request->github_username]);

        return back()->with('success', 'GitHub username saved.');
    }

    public function profile()
    {
        $user = auth()->user();
        return view('settings.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'          => 'required|min:2|max:100',
            'email'         => 'required|email|unique:users,email,' . auth()->id(),
            'phone'         => 'nullable|string|max:20',
            'work_location' => 'nullable|in:onsite,remote,hybrid',
        ]);

        $skills = null;
        if ($request->filled('skills_input')) {
            $skills = array_values(array_filter(array_map('trim', explode(',', $request->skills_input))));
        }

        $data = [
            'name'          => $request->name,
            'email'         => $request->email,
            'department'    => $request->department,
            'phone'         => $request->phone,
            'work_location' => $request->work_location,
        ];

        if ($skills !== null) {
            $data['skills'] = $skills;
        }

        auth()->user()->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        auth()->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function notifications()
    {
        $user     = auth()->user();
        $settings = $user->settings ?? [];
        return view('settings.notifications', compact('user', 'settings'));
    }

    public function updateNotifications(Request $request)
    {
        $user     = auth()->user();
        $settings = $user->settings ?? [];

        $settings['notifications'] = [
            'email_flags'          => $request->boolean('email_flags'),
            'email_blockers'       => $request->boolean('email_blockers'),
            'email_weekly_digest'  => $request->boolean('email_weekly_digest'),
            'email_team_joins'     => $request->boolean('email_team_joins'),
        ];

        $user->update(['settings' => $settings]);

        return back()->with('success', 'Notification preferences saved.');
    }

    public function deleteOrganization(Request $request)
    {
        $this->authorize('manage_organization');

        $request->validate(['confirmation' => 'required|in:DELETE']);

        $org         = auth()->user()->organization;
        $memberCount = User::where('organization_id', $org->id)->count();

        if ($memberCount > 1) {
            return back()->with('error', 'Remove all team members before deleting the organization.');
        }

        auth()->user()->update([
            'organization_id'   => null,
            'onboarding_status' => 'pending',
        ]);

        $org->delete();

        return redirect()->route('organization.create')
            ->with('info', 'Organization deleted successfully.');
    }

    public function platform()
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
        return view('settings.platform');
    }

    public function ai()
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
        $intents = AgentIntent::active()->orderBy('display_name')->get();
        return view('settings.ai', compact('intents'));
    }

    public function addIntentTrigger(Request $request, int $id)
    {
        $request->validate(['trigger' => 'required|string|min:2|max:80']);
        $intent   = AgentIntent::findOrFail($id);
        $triggers = $intent->triggers ?? [];
        $newTrigger = strtolower(trim($request->trigger));
        if (!in_array($newTrigger, $triggers)) {
            $triggers[] = $newTrigger;
            $intent->update(['triggers' => $triggers]);
            IntentMatcherService::clearCache();
        }
        return back()->with('success', "Trigger added to {$intent->display_name}.");
    }

    public function removeIntentTrigger(Request $request, int $id)
    {
        $request->validate(['trigger' => 'required|string']);
        $intent   = AgentIntent::findOrFail($id);
        $triggers = array_values(array_filter(
            $intent->triggers ?? [],
            fn ($t) => $t !== $request->trigger
        ));
        $intent->update(['triggers' => $triggers]);
        IntentMatcherService::clearCache();
        return back()->with('success', "Trigger removed from {$intent->display_name}.");
    }

    public function security()
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
        return view('settings.security');
    }

    public function audit()
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
        return view('settings.audit');
    }
}
