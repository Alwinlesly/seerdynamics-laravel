<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimesheetApprovalController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->inGroup(3)) {
            abort(403, 'Access Denied');
        }

        $consultants = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->whereIn('users_groups.group_id', [1, 2])
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->orderBy('users.first_name')
            ->get();

        $customers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->where('users_groups.group_id', 3)
            ->whereNotNull('users.company')
            ->where('users.company', '!=', '')
            ->select('users.id', 'users.company')
            ->orderBy('users.company')
            ->get();

        $projects = DB::table('projects')
            ->where('manager_id', $user->id)
            ->where('status', 1)
            ->select('id', 'project_id', 'title')
            ->orderBy('title')
            ->get();

        return view('timesheets.approvals', [
            'page_title' => 'Timesheet Approval - ' . company_name(),
            'current_user' => $user,
            'consultants' => $consultants,
            'customers' => $customers,
            'projects' => $projects,
        ]);
    }

    public function list(Request $request)
    {
        $user = auth()->user();

        if ($user->inGroup(3)) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $search = trim((string) $request->input('search', ''));

        $query = DB::table('weekly_timesheet as t')
            ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->join('projects as p', 'p.id', '=', 'wp.project_id')
            ->join('tasks as ts', 'ts.id', '=', 'wp.task_id')
            ->join('users as uc', 'uc.id', '=', 't.user_id')
            ->join('users as cust', 'cust.id', '=', 'wp.customer_id')
            ->where('t.status', 1)
            ->where('wp.status', 1)
            ->where('wh.status', 1)
            ->where('t.submit_or_draft', 'submit')
            ->where('p.manager_id', $user->id);

        if ($request->filled('user_id')) {
            $query->where('t.user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('customer')) {
            $query->where('wp.customer_id', (int) $request->input('customer'));
        }

        if ($request->filled('project')) {
            $query->where('wp.project_id', (int) $request->input('project'));
        }

        if ($request->input('status', '') !== '') {
            $query->where('wp.approved_status', (int) $request->input('status'));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('uc.first_name', 'like', "%{$search}%")
                    ->orWhere('uc.last_name', 'like', "%{$search}%")
                    ->orWhere('cust.company', 'like', "%{$search}%")
                    ->orWhere('t.work_week', 'like', "%{$search}%")
                    ->orWhere('p.title', 'like', "%{$search}%")
                    ->orWhere('ts.title', 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->distinct('wp.id')->count('wp.id');

        $rows = $query
            ->groupBy('wp.id', 't.id', 'wp.billable', 'wp.approved_status', 'p.title', 'ts.title', 'uc.first_name', 'uc.last_name', 'cust.company', 't.work_week')
            ->select(
                't.id as timesheet_row_id',
                'wp.id as time_pjt_id',
                'wp.billable',
                'wp.approved_status',
                'p.title as project_title',
                'ts.title as task_title',
                'uc.first_name as consultant_first_name',
                'uc.last_name as consultant_last_name',
                'cust.company as customer_name',
                't.work_week',
                DB::raw('COALESCE(SUM(CAST(wh.hours AS DECIMAL(10,2))),0) as total_hour')
            )
            ->orderBy('t.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'timesheet_id' => 'T' . str_pad($row->timesheet_row_id, 5, '0', STR_PAD_LEFT),
                    'consultant' => trim(($row->consultant_first_name ?? '') . ' ' . ($row->consultant_last_name ?? '')),
                    'customer' => $row->customer_name,
                    'billable' => (int) $row->billable,
                    'project' => $row->project_title,
                    'task' => $row->task_title,
                    'work_week' => $row->work_week,
                    'totalhour' => (float) $row->total_hour,
                    'approved_status' => (int) $row->approved_status,
                    'time_pjt_id' => (int) $row->time_pjt_id,
                ];
            })
            ->values();

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    public function approve($id)
    {
        return $this->updateApproval((int) $id, 1);
    }

    public function reject($id)
    {
        return $this->updateApproval((int) $id, 2);
    }

    public function details(Request $request)
    {
        $user = auth()->user();

        if ($user->inGroup(3)) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }

        $detailId = (int) $request->input('timesheet_project_table_id');
        if ($detailId <= 0) {
            return response()->json(['error' => true, 'message' => 'Invalid timesheet row'], 422);
        }

        // Enforce manager ownership to mirror approval scope.
        $hasAccess = DB::table('weekly_timesheet_project_task_details as wp')
            ->join('projects as p', 'p.id', '=', 'wp.project_id')
            ->where('wp.id', $detailId)
            ->where('p.manager_id', $user->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }

        $daystotal = DB::table('weekly_timesheet_project_task_hours as wh')
            ->where('wh.timesheet_project_task_id', $detailId)
            ->where('wh.status', 1)
            ->orderBy('wh.date')
            ->select('wh.date', 'wh.hours as totalhour', 'wh.note')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => date('D d/m', strtotime((string) $row->date)),
                    'totalhr' => $row->totalhour,
                    'note' => $row->note ?? '',
                ];
            })
            ->values();

        return response()->json([
            'error' => false,
            'daystotal' => $daystotal,
        ]);
    }

    private function updateApproval(int $detailId, int $status)
    {
        $user = auth()->user();

        if ($user->inGroup(3)) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }

        if ($detailId <= 0) {
            return response()->json(['error' => true, 'message' => 'Invalid request'], 422);
        }

        $updated = DB::table('weekly_timesheet_project_task_details as wp')
            ->join('projects as p', 'p.id', '=', 'wp.project_id')
            ->where('wp.id', $detailId)
            ->where('p.manager_id', $user->id)
            ->update([
                'wp.approved_status' => $status,
                'wp.approvedon' => now(),
                'wp.approvedby' => $user->id,
            ]);

        if (!$updated) {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong. Try again.',
            ], 422);
        }

        return response()->json([
            'error' => false,
            'message' => $status === 1 ? 'Timesheet approved successfully.' : 'Timesheet rejected successfully.',
        ]);
    }
}

