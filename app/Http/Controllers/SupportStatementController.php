<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportStatementController extends Controller
{
    /**
     * Display the support statement page with filters
     */
    public function index()
    {
        $user = auth()->user();
        
        // Only admins and consultants with project_view permission
        if ($user->inGroup(3)) {
            // For customers: get their parent company id
            $customerId = $this->getCuserParentCompanyId($user->id);
            $customers = collect(); // customers won't see customer dropdown
        } else {
            $customerId = null;
            // Get customers list (group 3 users with company)
            $customers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->where('users_groups.group_id', 3)
                ->whereNotNull('users.company')
                ->where('users.company', '!=', '')
                ->select('users.id', 'users.company')
                ->get();
        }
        
        // Get project types
        $projectTypes = DB::table('project_type')
            ->select('id', 'title')
            ->get();
        
        // Get projects based on user role
        if ($user->inGroup(1)) {
            // Admin: all projects
            $projects = DB::table('project_users as pu')
                ->join('projects as p', 'pu.project_id', '=', 'p.id')
                ->select('p.id', 'p.project_id', 'p.title')
                ->distinct()
                ->orderBy('p.created', 'desc')
                ->get();
        } else {
            // Non-admin: projects where user is assigned
            $projects = DB::table('project_users as pu')
                ->join('projects as p', 'pu.project_id', '=', 'p.id')
                ->where('pu.user_id', $user->id)
                ->select('p.id', 'p.project_id', 'p.title')
                ->distinct()
                ->orderBy('p.created', 'desc')
                ->get();
        }
        
        $data = [
            'page_title' => 'Support Statement - ' . company_name(),
            'current_user' => $user,
            'customers' => $customers,
            'projects' => $projects,
            'project_types' => $projectTypes,
            'customer_id' => $customerId,
        ];
        
        return view('support-statement.index', $data);
    }
    
    /**
     * Generate report view via AJAX
     */
    public function reportView(Request $request)
    {
        $user = auth()->user();
        
        $customer = $request->input('customer');
        $project = $request->input('project');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        // Get customer details
        $customerData = DB::table('users')
            ->select('company', 'id')
            ->where('id', $customer)
            ->first();
        
        // Get project details with status
        $projectData = DB::table('projects as p')
            ->join('project_status as ps', 'ps.id', '=', 'p.status')
            ->where('p.id', $project)
            ->select('p.*', 'ps.title as project_status')
            ->first();
        
        // Get total released hours for this project in the date range
        $utilizedHours = DB::table('weekly_timesheet as t')
            ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)
            ->where('wp.status', 1)
            ->where('wh.release_status', 1)
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') >= ?", [date('Y-m-d', strtotime($fromDate))])
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') <= ?", [date('Y-m-d', strtotime($toDate))])
            ->select(DB::raw('COALESCE(SUM(wh.released_hour), 0) as totalhour'))
            ->first();
        
        // Get hours utilized up to from_date (balance c/f)
        $utilizedUptoFromDate = DB::table('weekly_timesheet as t')
            ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)
            ->where('wp.status', 1)
            ->where('wh.release_status', 1)
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') < ?", [date('Y-m-d', strtotime($fromDate))])
            ->select(DB::raw('COALESCE(SUM(wh.released_hour), 0) as totalhour'))
            ->first();
        
        // Get tasks with released hours in this project+date range
        $tasks = DB::table('tasks as ts')
            ->join('weekly_timesheet_project_task_details as wt', 'wt.task_id', '=', 'ts.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wt.id')
            ->join('task_status as tst', 'tst.id', '=', 'ts.status')
            ->where('ts.project_id', $project)
            ->where('wh.release_status', 1)
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') >= ?", [date('Y-m-d', strtotime($fromDate))])
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') <= ?", [date('Y-m-d', strtotime($toDate))])
            ->groupBy('ts.id', 'ts.title', 'ts.due_date', 'ts.status', 'tst.title')
            ->select('ts.id', 'ts.title', 'ts.due_date', 'tst.title as task_status')
            ->get();
        
        // For each task, get detailed released hours
        $taskDetails = [];
        foreach ($tasks as $task) {
            $hours = DB::table('weekly_timesheet as t')
                ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
                ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
                ->join('users as u', 'u.id', '=', 't.user_id')
                ->where('wp.task_id', $task->id)
                ->where('wh.status', 1)
                ->where('wp.status', 1)
                ->where('wh.release_status', 1)
                ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') >= ?", [date('Y-m-d', strtotime($fromDate))])
                ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') <= ?", [date('Y-m-d', strtotime($toDate))])
                ->select('wh.released_hour as totalhour', 'wh.date', 'u.first_name as consultant')
                ->get();
            
            $totalHours = 0;
            $hourEntries = [];
            foreach ($hours as $hour) {
                if ($hour->totalhour > 0) {
                    $hourEntries[] = [
                        'consultant' => $hour->consultant,
                        'date' => date('d-M-Y', strtotime($hour->date)),
                        'totalhr' => $hour->totalhour,
                    ];
                    $totalHours += $hour->totalhour;
                }
            }
            
            $taskDetails[] = [
                'id' => $task->id,
                'ticket_no' => '#' . str_pad($task->id, 5, '0', STR_PAD_LEFT),
                'title' => $task->title,
                'created_at' => date('d-M-Y', strtotime($task->due_date)),
                'status' => $task->task_status,
                'hours' => $hourEntries,
                'total_hours' => $totalHours,
            ];
        }
        
        // Calculate summary values
        $projectHours = $projectData->hours ?? 0;
        $balanceCf = $projectHours - ($utilizedUptoFromDate->totalhour ?? 0);
        $hoursUtilized = $utilizedHours->totalhour ?? 0;
        $closingBalance = $balanceCf - $hoursUtilized;
        
        return response()->json([
            'customer' => $customerData,
            'project' => $projectData,
            'balance_cf' => $balanceCf,
            'hours_utilized' => $hoursUtilized,
            'closing_balance' => $closingBalance,
            'tasks' => $taskDetails,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }
    
    /**
     * Generate printable support statement (opens in new tab)
     */
    public function generatePrint(Request $request)
    {
        $customer = $request->input('customer');
        $project = $request->input('project');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        // Get customer details
        $customerData = DB::table('users')
            ->select('company', 'id')
            ->where('id', $customer)
            ->first();
        
        // Get project details with status
        $projectData = DB::table('projects as p')
            ->join('project_status as ps', 'ps.id', '=', 'p.status')
            ->where('p.id', $project)
            ->select('p.*', 'ps.title as project_status')
            ->first();
        
        // Get total released hours in date range
        $utilizedHours = DB::table('weekly_timesheet as t')
            ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)->where('wp.status', 1)->where('wh.release_status', 1)
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') >= ?", [date('Y-m-d', strtotime($fromDate))])
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') <= ?", [date('Y-m-d', strtotime($toDate))])
            ->select(DB::raw('COALESCE(SUM(wh.released_hour), 0) as totalhour'))
            ->first();
        
        // Get hours utilized up to from_date
        $utilizedUptoFromDate = DB::table('weekly_timesheet as t')
            ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)->where('wp.status', 1)->where('wh.release_status', 1)
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') < ?", [date('Y-m-d', strtotime($fromDate))])
            ->select(DB::raw('COALESCE(SUM(wh.released_hour), 0) as totalhour'))
            ->first();
        
        // Get tasks
        $tasks = DB::table('tasks as ts')
            ->join('weekly_timesheet_project_task_details as wt', 'wt.task_id', '=', 'ts.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wt.id')
            ->join('task_status as tst', 'tst.id', '=', 'ts.status')
            ->where('ts.project_id', $project)->where('wh.release_status', 1)
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') >= ?", [date('Y-m-d', strtotime($fromDate))])
            ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') <= ?", [date('Y-m-d', strtotime($toDate))])
            ->groupBy('ts.id', 'ts.title', 'ts.due_date', 'ts.status', 'tst.title')
            ->select('ts.id', 'ts.title', 'ts.due_date', 'tst.title as task_status')
            ->get();
        
        $taskDetails = [];
        foreach ($tasks as $task) {
            $hours = DB::table('weekly_timesheet as t')
                ->join('weekly_timesheet_project_task_details as wp', 'wp.timesheet_id', '=', 't.id')
                ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
                ->join('users as u', 'u.id', '=', 't.user_id')
                ->where('wp.task_id', $task->id)
                ->where('wh.status', 1)->where('wp.status', 1)->where('wh.release_status', 1)
                ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') >= ?", [date('Y-m-d', strtotime($fromDate))])
                ->whereRaw("DATE_FORMAT(wh.date,'%Y-%m-%d') <= ?", [date('Y-m-d', strtotime($toDate))])
                ->select('wh.released_hour as totalhour', 'wh.date', 'u.first_name as consultant')
                ->get();
            
            $totalHours = 0;
            $hourEntries = [];
            foreach ($hours as $hour) {
                if ($hour->totalhour > 0) {
                    $hourEntries[] = [
                        'consultant' => $hour->consultant,
                        'date' => date('d-M-Y', strtotime($hour->date)),
                        'totalhr' => $hour->totalhour,
                    ];
                    $totalHours += $hour->totalhour;
                }
            }
            
            $taskDetails[] = [
                'ticket_no' => '#' . str_pad($task->id, 5, '0', STR_PAD_LEFT),
                'title' => $task->title,
                'created_at' => date('d-M-Y', strtotime($task->due_date)),
                'status' => $task->task_status,
                'hours' => $hourEntries,
                'total_hours' => $totalHours,
            ];
        }
        
        $projectHours = $projectData->hours ?? 0;
        $balanceCf = $projectHours - ($utilizedUptoFromDate->totalhour ?? 0);
        $hoursUtilized = $utilizedHours->totalhour ?? 0;
        $closingBalance = $balanceCf - $hoursUtilized;
        
        return view('support-statement.print', [
            'customer' => $customerData,
            'project' => $projectData,
            'balance_cf' => $balanceCf,
            'hours_utilized' => $hoursUtilized,
            'closing_balance' => $closingBalance,
            'tasks' => $taskDetails,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }
    
    /**
     * Get projects filtered by customer and/or project type
     */
    public function getProjectsByCustomer(Request $request)
    {
        try {
            $query = Project::query();
            
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
    
    /**
     * Get the parent company ID for a customer user
     */
    private function getCuserParentCompanyId($userId)
    {
        $user = User::find($userId);
        if ($user && $user->company) {
            // Try to find parent company user
            $parent = User::where('company', $user->company)
                ->join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->where('users_groups.group_id', 3)
                ->select('users.id')
                ->first();
            return $parent ? $parent->id : $userId;
        }
        return $userId;
    }
}
