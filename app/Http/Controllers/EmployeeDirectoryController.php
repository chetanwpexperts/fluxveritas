<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;
        $isHr  = $user->hasAnyRole(['hr', 'admin', 'owner', 'super_admin']);

        $query = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->with(['employeeProfile', 'department', 'roles']);

        if (!$isHr) {
            $query->whereHas('employeeProfile', fn($q) => $q->where('is_directory_visible', true));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', "%{$s}%")
                  ->orWhere('email', 'LIKE', "%{$s}%")
                  ->orWhereHas('employeeProfile', function ($ep) use ($s) {
                      $ep->where('designation', 'LIKE', "%{$s}%")
                         ->orWhereRaw("LOWER(JSON_UNQUOTE(skills)) LIKE ?", ['%' . strtolower($s) . '%']);
                  });
            });
        }

        if ($request->filled('dept')) {
            $query->where('department_id', $request->dept);
        }

        if ($request->filled('skill')) {
            $query->whereHas('employeeProfile', fn($q) => $q->whereJsonContains('skills', $request->skill));
        }

        $totalCount  = User::where('organization_id', $orgId)->where('is_active', true)
                           ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))->count();
        $users       = $query->orderBy('name')->paginate(24)->withQueryString();
        $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();

        $allSkills = EmployeeProfile::whereHas('user', fn($q) => $q->where('organization_id', $orgId))
            ->pluck('skills')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('directory.index', compact('users', 'departments', 'allSkills', 'isHr', 'totalCount'));
    }

    public function show(User $user)
    {
        $auth = auth()->user();
        abort_if($user->organization_id !== $auth->organization_id && !$auth->hasRole('super_admin'), 403);
        abort_if($user->hasRole('super_admin'), 404);

        $user->load(['department', 'employeeProfile', 'roles', 'reportingManager']);

        $canSeePrivate = $auth->id === $user->id
                      || $auth->hasAnyRole(['hr', 'admin', 'owner']);

        return view('directory.show', compact('user', 'canSeePrivate'));
    }

    public function editProfile()
    {
        $user    = auth()->user();
        $profile = $user->employeeProfile ?? new EmployeeProfile(['user_id' => $user->id]);
        $isHrEdit = false;

        return view('directory.edit', compact('user', 'profile', 'isHrEdit'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'designation'          => 'nullable|string|max:100',
            'phone'                => 'nullable|string|max:20',
            'alternate_email'      => 'nullable|email|max:255',
            'date_of_birth'        => 'nullable|date',
            'date_of_joining'      => 'nullable|date',
            'skills'               => 'nullable|string',
            'bio'                  => 'nullable|string|max:500',
            'github_username'      => 'nullable|string|max:100',
            'linkedin_url'         => 'nullable|url|max:255',
            'profile_photo'        => 'nullable|image|max:2048',
            'is_directory_visible' => 'nullable|boolean',
        ]);

        $profileData = array_diff_key($data, ['skills' => '', 'profile_photo' => '']);
        $profileData['skills'] = $this->parseSkills($data['skills'] ?? null);
        $profileData['is_directory_visible'] = (bool) ($data['is_directory_visible'] ?? true);

        if ($request->hasFile('profile_photo')) {
            $ext  = $request->file('profile_photo')->getClientOriginalExtension();
            $path = $request->file('profile_photo')->storeAs('profiles', $user->id . '.' . $ext, 'public');
            $profileData['profile_photo'] = $path;
        }

        EmployeeProfile::updateOrCreate(['user_id' => $user->id], $profileData);

        return redirect()->route('directory.show', $user)->with('success', 'Profile updated.');
    }

    public function hrEdit(User $user)
    {
        $auth = auth()->user();
        abort_if(!$auth->hasAnyRole(['hr', 'admin', 'owner', 'super_admin']), 403);
        abort_if($user->organization_id !== $auth->organization_id && !$auth->hasRole('super_admin'), 403);

        $profile  = $user->employeeProfile ?? new EmployeeProfile(['user_id' => $user->id]);
        $isHrEdit = true;

        return view('directory.edit', compact('user', 'profile', 'isHrEdit'));
    }

    public function hrUpdate(Request $request, User $user)
    {
        $auth = auth()->user();
        abort_if(!$auth->hasAnyRole(['hr', 'admin', 'owner', 'super_admin']), 403);
        abort_if($user->organization_id !== $auth->organization_id && !$auth->hasRole('super_admin'), 403);

        $data = $request->validate([
            'designation'     => 'nullable|string|max:100',
            'phone'           => 'nullable|string|max:20',
            'alternate_email' => 'nullable|email|max:255',
            'date_of_birth'   => 'nullable|date',
            'date_of_joining' => 'nullable|date',
            'skills'          => 'nullable|string',
            'bio'             => 'nullable|string|max:500',
            'github_username' => 'nullable|string|max:100',
            'linkedin_url'    => 'nullable|url|max:255',
            'profile_photo'   => 'nullable|image|max:2048',
        ]);

        $profileData = array_diff_key($data, ['skills' => '', 'profile_photo' => '']);
        $profileData['skills'] = $this->parseSkills($data['skills'] ?? null);

        if ($request->hasFile('profile_photo')) {
            $ext  = $request->file('profile_photo')->getClientOriginalExtension();
            $path = $request->file('profile_photo')->storeAs('profiles', $user->id . '.' . $ext, 'public');
            $profileData['profile_photo'] = $path;
        }

        EmployeeProfile::updateOrCreate(['user_id' => $user->id], $profileData);

        return redirect()->route('directory.show', $user)->with('success', 'Profile updated for ' . $user->name . '.');
    }

    private function parseSkills(?string $raw): array
    {
        if (empty($raw)) return [];
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
