<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimesheetController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $orgId = $user->organization_id;
        $today = now()->toDateString();

        $todayTimesheet = Timesheet::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $query = Timesheet::where('organization_id', $orgId)->with('user');
        if (!$user->hasAnyRole(['admin', 'owner', 'super_admin', 'hr'])) {
            $query->where('user_id', $user->id);
        }
        $timesheets = $query->latest('work_date')->take(30)->get();

        return view('timesheets.index', compact('todayTimesheet', 'timesheets'));
    }

    public function clockIn()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        Timesheet::updateOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            [
                'organization_id' => $user->organization_id,
                'clock_in'        => now()->toTimeString(),
                'status'          => 'present',
            ]
        );

        return back()->with('success', 'Clocked in successfully! Have a productive day.');
    }

    public function clockOut()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $timesheet = Timesheet::where('user_id', $user->id)->where('work_date', $today)->first();

        if ($timesheet && $timesheet->clock_in) {
            $clockIn  = \Carbon\Carbon::parse($timesheet->clock_in);
            $clockOut = now();
            $hours    = round($clockOut->diffInMinutes($clockIn) / 60, 2);

            $timesheet->update([
                'clock_out'   => $clockOut->toTimeString(),
                'total_hours' => $hours,
            ]);
        }

        return back()->with('success', 'Clocked out successfully!');
    }
}
