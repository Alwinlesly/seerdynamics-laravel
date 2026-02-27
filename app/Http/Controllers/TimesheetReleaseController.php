<?php

namespace App\Http\Controllers;

use App\Models\WeeklyTimesheet;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimesheetReleaseController extends Controller
{
    /**
     * Display timesheet release page
     */
    public function index()
    {
        $user = auth()->user();
        
        // Only admins and consultants can access (not customers - group 3)
        if ($user->inGroup(3)) {
            abort(403, 'Access Denied');
        }
        
        // Get consultants list (admins and consultants only - groups 1 & 2)
        $consultants = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->whereIn('users_groups.group_id', [1, 2])
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->get();
        
        // Get customers list (group 3)
        $customers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->where('users_groups.group_id', 3)
            ->whereNotNull('users.company')
            ->where('users.company', '!=', '')
            ->select('users.id', 'users.company')
            ->get();
        
        // Get projects
        $projects = Project::where('status', 1)
            ->select('id', 'project_id', 'title')
            ->get();
        
        // Get project types
        $projectTypes = DB::table('project_type')
            ->select('id', 'title')
            ->get();
        
        $data = [
            'page_title' => 'Timesheet Release - ' . company_name(),
            'current_user' => $user,
            'consultants' => $consultants,
            'customers' => $customers,
            'projects' => $projects,
            'project_types' => $projectTypes,
        ];
        
        return view('timesheets.release', $data);
    }
    
    /**
     * Get timesheet release data via AJAX
     */
    public function getData(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Only admins and consultants
            if ($user->inGroup(3)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            // Pagination parameters
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');
            $search = $request->input('search', '');
            
            // Validate sort column to prevent SQL injection
            $allowedSorts = ['id', 'date', 'user_id'];
            if (!in_array($sort, $allowedSorts)) {
                $sort = 'id';
            }
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            
            // Build base query
            $query = DB::table('weekly_timesheet as t')
                ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
                ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
                ->join('projects as p', 'p.id', '=', 'wp.project_id')
                ->join('tasks as ts', 'ts.id', '=', 'wp.task_id')
                ->join('task_status as tst', 'ts.status', '=', 'tst.id')
                ->join('users as uc', 'uc.id', '=', 't.user_id')
                ->join('users as cust', 'cust.id', '=', 'wp.customer_id')
                ->leftJoin('users as u', 'u.id', '=', 'p.manager_id')
                ->where('t.status', 1)
                ->where('t.submit_or_draft', 'submit')
                ->where('wp.status', 1)
                ->where('wh.status', 1)
                ->where('wh.hours', '>', 0);
            
            // Apply filters
            if ($request->filled('user_id')) {
                $query->where('t.user_id', $request->user_id);
            }
            
            if ($request->filled('customer')) {
                $query->where('wp.customer_id', $request->customer);
            }
            
            if ($request->filled('project')) {
                $query->where('wp.project_id', $request->project);
            }
            
            // Status filter — only apply if it's a valid numeric value (0 or 1)
            $status = $request->input('status', '');
            if ($status !== '' && $status !== null && is_numeric($status)) {
                $query->where('wh.release_status', $status);
            }
            
            if ($request->filled('fromdate')) {
                $fromDate = date('Y-m-d', strtotime($request->fromdate));
                $query->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') >= ?", [$fromDate]);
            }
            
            if ($request->filled('todate')) {
                $toDate = date('Y-m-d', strtotime($request->todate));
                $query->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') <= ?", [$toDate]);
            }
            
            if ($request->filled('projecttype')) {
                $query->where('p.ptype', $request->projecttype);
            }
            
            // Search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('uc.first_name', 'like', "%{$search}%")
                      ->orWhere('t.id', 'like', "%{$search}%")
                      ->orWhere('tst.title', 'like', "%{$search}%")
                      ->orWhere('p.title', 'like', "%{$search}%")
                      ->orWhere('ts.title', 'like', "%{$search}%");
                });
            }
            
            // Get total count
            $total = (clone $query)->count();
            
            // Get paginated results with subqueries to avoid N+1
            $results = $query->select(
                    't.id',
                    'wh.id as releaseid',
                    'wh.release_status',
                    'wh.note',
                    'wh.released_hour',
                    'tst.title as ticket_status',
                    'ts.id as task_id',
                    'p.project_id',
                    'wp.id as time_pjt_id',
                    'wp.approved_status',
                    'wp.billable',
                    'wh.date as booked_date',
                    'wh.hours as totalhour',
                    'p.title as project',
                    'ts.title as task_name',
                    'uc.first_name as consultant_name',
                    'cust.company as customer_name'
                )
                ->orderBy("t.{$sort}", $order)
                ->skip($offset)
                ->take($limit)
                ->get();
            
            if ($results->isEmpty()) {
                return response()->json(['total' => $total, 'rows' => []]);
            }
            
            // Batch-load all related data to avoid N+1 queries
            $taskIds = $results->pluck('task_id')->unique()->values()->toArray();
            
            // Batch: billable hours per task
            $billableMap = DB::table('weekly_timesheet as t')
                ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
                ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
                ->where('t.status', 1)
                ->where('wp.status', 1)
                ->where('wh.status', 1)
                ->where('wp.billable', 1)
                ->whereIn('wp.task_id', $taskIds)
                ->groupBy('wp.task_id')
                ->select('wp.task_id', DB::raw('COALESCE(SUM(wh.hours), 0) as total'))
                ->pluck('total', 'task_id');
            
            // Batch: non-billable hours per task
            $nonBillableMap = DB::table('weekly_timesheet as t')
                ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
                ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
                ->where('t.status', 1)
                ->where('wp.status', 1)
                ->where('wh.status', 1)
                ->where('wp.billable', 0)
                ->whereIn('wp.task_id', $taskIds)
                ->groupBy('wp.task_id')
                ->select('wp.task_id', DB::raw('COALESCE(SUM(wh.hours), 0) as total'))
                ->pluck('total', 'task_id');
            
            // Batch: estimates per task
            $estimateMap = DB::table('task_estimate')
                ->whereIn('task_id', $taskIds)
                ->select('task_id', 'estimate_hours', 'estimate_status')
                ->get()
                ->keyBy('task_id');
            
            // Batch: hours released so far per task (matching CI: scoped by task_id)
            $releasedSoFarMap = DB::table('weekly_timesheet as t')
                ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
                ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
                ->whereIn('wp.task_id', $taskIds)
                ->groupBy('wp.task_id')
                ->select('wp.task_id', DB::raw('COALESCE(SUM(wh.released_hour), 0) as total'))
                ->pluck('total', 'task_id');
            
            $rows = [];
            
            foreach ($results as $result) {
                $taskId = $result->task_id;
                $estimate = $estimateMap->get($taskId);
                
                // Released hour default logic (matching CI):
                // If billable: default to total_hour when released_hour is empty/0
                // If non-billable: default to 0 when released_hour is empty/0
                $releasedHour = $result->released_hour;
                if ($releasedHour > 0) {
                    // Keep existing value
                } elseif ($result->billable == 1) {
                    $releasedHour = $result->totalhour;
                } else {
                    $releasedHour = 0;
                }
                
                $row = [
                    'id' => $result->releaseid,
                    'timesheet_id' => 'T' . str_pad($result->id, 5, '0', STR_PAD_LEFT),
                    'consultant' => $result->consultant_name,
                    'date' => date('d-M-Y', strtotime($result->booked_date)),
                    'approved_status' => $result->approved_status,
                    'release_status' => $result->release_status,
                    'billable' => $result->billable,
                    'customer' => $result->customer_name,
                    'project_id' => $result->project_id,
                    'project' => $result->project,
                    'ticket_id' => '#' . str_pad($taskId, 5, '0', STR_PAD_LEFT),
                    'ticket_name' => $result->task_name,
                    'ticket_status' => $result->ticket_status,
                    'total_estimate' => $estimate->estimate_hours ?? '',
                    'estimate_approved' => $estimate->estimate_status ?? 0,
                    'total_billable_hour' => $billableMap->get($taskId, 0),
                    'total_nonbillable_hour' => $nonBillableMap->get($taskId, 0),
                    'total_hour_released_sofar' => $releasedSoFarMap->get($taskId, 0),
                    'total_hour' => $result->totalhour,
                    'released_hour' => $releasedHour,
                    'note' => $result->note ?? '',
                ];
                
                $rows[] = $row;
            }
            
            return response()->json([
                'total' => $total,
                'rows' => $rows
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Timesheet Release Error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error loading data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Save released timesheets
     */
    public function saveRelease(Request $request)
    {
        try {
            $user = auth()->user();
            
            if ($user->inGroup(3)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            $request->validate([
                'releaseids' => 'required|array',
                'releaseids.*' => 'required|integer'
            ]);
            
            $releaseIds = $request->input('releaseids');
            
            foreach ($releaseIds as $id) {
                $releaseHour = $request->input('release_amt_' . $id);
                
                DB::table('weekly_timesheet_project_task_hours')
                    ->where('id', $id)
                    ->update([
                        'released_hour' => $releaseHour,
                        'release_status' => 1,
                        'released_on' => now()
                    ]);
            }
            
            return response()->json([
                'error' => false,
                'message' => 'Timesheets released successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Save Release Error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error saving release: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get projects by customer and type
     */
    public function getProjectsByCustomer(Request $request)
    {
        try {
            $query = Project::where('status', 1);
            
            if ($request->filled('customerid')) {
                $query->where('client_id', $request->customerid);
            }
            
            if ($request->filled('projecttype')) {
                $query->where('ptype', $request->projecttype);
            }
            
            $projects = $query->select('id', 'project_id', 'title')->get();
            
            return response()->json([
                'error' => false,
                'projects' => $projects
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
