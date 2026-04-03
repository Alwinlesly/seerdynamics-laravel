<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SupportStatementController extends Controller
{
    /**
     * Display the support statement page with filters
     */
    public function index()
    {
        $user = auth()->user();
        
        // Old flow parity:
        // - Admin: customer dropdown visible
        // - Non-admin: customer hidden and prefilled (if customer admin), otherwise empty
        $isAdmin = $user->inGroup(1);
        $isCustomerAdmin = $user->inGroup(3);

        if ($isAdmin) {
            $customerId = null;
            $customers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->where('users_groups.group_id', 3)
                ->whereNotNull('users.company')
                ->where('users.company', '!=', '')
                ->select('users.id', 'users.company')
                ->orderBy('users.company')
                ->get();
        } else {
            $customers = collect();
            $customerId = $isCustomerAdmin ? ($this->getCuserParentCompanyId($user->id) ?: $user->id) : null;
        }
        
        // Get project types
        $projectTypes = DB::table('project_type')
            ->select('id', 'title')
            ->get();
        
        // Get projects based on old support statement behavior
        if ($isAdmin) {
            $projects = DB::table('projects as p')
                ->select('p.id', 'p.project_id', 'p.title')
                ->orderByDesc('p.created')
                ->get();
        } elseif ($isCustomerAdmin) {
            $clientIds = $user->getCustomerClientIds();
            $projects = DB::table('projects as p')
                ->whereIn('p.client_id', $clientIds)
                ->where('p.is_visible', 0)
                ->select('p.id', 'p.project_id', 'p.title')
                ->orderByDesc('p.created')
                ->get();
        } else {
            $projects = DB::table('project_users as pu')
                ->join('projects as p', 'pu.project_id', '=', 'p.id')
                ->where('pu.user_id', $user->id)
                ->select('p.id', 'p.project_id', 'p.title')
                ->groupBy('p.id', 'p.project_id', 'p.title', 'p.created')
                ->orderByDesc('p.created')
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
        $project = (int) $request->input('project');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $fromYmd = date('Y-m-d', strtotime($fromDate));
        $toYmd = date('Y-m-d', strtotime($toDate));

        if (!$this->canAccessProject($user, $project)) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }

        $customerData = DB::table('users')
            ->select('company', 'id')
            ->where('id', $customer)
            ->first();

        $projectData = DB::table('projects as p')
            ->join('project_status as ps', 'ps.id', '=', 'p.status')
            ->where('p.id', $project)
            ->select('p.*', 'ps.title as project_status')
            ->first();

        $hoursUtilized = DB::table('weekly_timesheet_project_task_details as wp')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)
            ->where('wp.status', 1)
            ->where('wh.release_status', 1)
            ->where('wh.date', '>=', $fromYmd)
            ->where('wh.date', '<=', $toYmd)
            ->sum('wh.released_hour');

        $utilizedUptoFromDate = DB::table('weekly_timesheet_project_task_details as wp')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)
            ->where('wp.status', 1)
            ->where('wh.release_status', 1)
            ->where('wh.date', '<', $fromYmd)
            ->sum('wh.released_hour');

        // Single query for all task-hour rows in selected range (avoids N+1 and speeds up page)
        $rows = DB::table('tasks as ts')
            ->join('weekly_timesheet_project_task_details as wt', 'wt.task_id', '=', 'ts.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wt.id')
            ->join('task_status as tst', 'tst.id', '=', 'ts.status')
            ->join('weekly_timesheet as t', 't.id', '=', 'wt.timesheet_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('ts.project_id', $project)
            ->where('wh.status', 1)
            ->where('wt.status', 1)
            ->where('wh.release_status', 1)
            ->where('wh.date', '>=', $fromYmd)
            ->where('wh.date', '<=', $toYmd)
            ->where('wh.released_hour', '>', 0)
            ->orderBy('ts.id')
            ->orderBy('wh.date')
            ->select(
                'ts.id as task_id',
                'ts.title as task_title',
                'ts.due_date',
                'tst.title as task_status',
                'wh.date',
                'wh.released_hour as totalhour',
                'u.first_name as consultant'
            )
            ->get();

        $taskMap = [];
        foreach ($rows as $row) {
            if (!isset($taskMap[$row->task_id])) {
                $taskMap[$row->task_id] = [
                    'id' => (int) $row->task_id,
                    'ticket_no' => '#' . str_pad($row->task_id, 5, '0', STR_PAD_LEFT),
                    'title' => $row->task_title,
                    'created_at' => date('d-M-Y', strtotime($row->due_date)),
                    'status' => $row->task_status,
                    'grouped_hours_map' => [],
                    'detailed_hours' => [],
                    'total_hours' => 0,
                ];
            }

            $monthKey = date('M-Y', strtotime($row->date));
            if (!isset($taskMap[$row->task_id]['grouped_hours_map'][$monthKey])) {
                $taskMap[$row->task_id]['grouped_hours_map'][$monthKey] = 0;
            }
            $taskMap[$row->task_id]['grouped_hours_map'][$monthKey] += (float) $row->totalhour;

            $taskMap[$row->task_id]['detailed_hours'][] = [
                'consultant' => $row->consultant,
                'date' => date('d-M-Y', strtotime($row->date)),
                'totalhr' => (float) $row->totalhour,
            ];
            $taskMap[$row->task_id]['total_hours'] += (float) $row->totalhour;
        }

        $taskDetails = [];
        foreach ($taskMap as $task) {
            $groupedHours = [];
            foreach ($task['grouped_hours_map'] as $month => $hours) {
                $groupedHours[] = [
                    'date' => $month,
                    'totalhr' => $hours,
                ];
            }
            unset($task['grouped_hours_map']);
            $task['grouped_hours'] = $groupedHours;
            $taskDetails[] = $task;
        }

        $balanceCf = ($projectData->hours ?? 0) - $utilizedUptoFromDate;
        $closingBalance = $balanceCf - $hoursUtilized;
        $showConsultant = (bool) $user->inGroup(1);

        return response()->json([
            'error' => false,
            'customer' => $customerData,
            'project' => $projectData,
            'balance_cf' => $balanceCf,
            'hours_utilized' => $hoursUtilized,
            'closing_balance' => $closingBalance,
            'tasks' => $taskDetails,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'show_consultant' => $showConsultant,
        ]);
    }
    
    /**
     * Generate support statement PDF download
     */
    public function generatePrint(Request $request)
    {
        $user = auth()->user();
        $customer = $request->input('customer');
        $project = (int) $request->input('project');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $fromYmd = date('Y-m-d', strtotime($fromDate));
        $toYmd = date('Y-m-d', strtotime($toDate));
        $showConsultant = $user->inGroup(1) && (bool) $request->input('show_consultant');

        if (!$this->canAccessProject($user, $project)) {
            abort(403, 'Access Denied');
        }

        $customerData = DB::table('users')
            ->select('company', 'id')
            ->where('id', $customer)
            ->first();

        $projectData = DB::table('projects as p')
            ->join('project_status as ps', 'ps.id', '=', 'p.status')
            ->where('p.id', $project)
            ->select('p.*', 'ps.title as project_status')
            ->first();

        $hoursUtilized = DB::table('weekly_timesheet_project_task_details as wp')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)
            ->where('wp.status', 1)
            ->where('wh.release_status', 1)
            ->where('wh.date', '>=', $fromYmd)
            ->where('wh.date', '<=', $toYmd)
            ->sum('wh.released_hour');

        $utilizedUptoFromDate = DB::table('weekly_timesheet_project_task_details as wp')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wp.id')
            ->where('wp.project_id', $project)
            ->where('wh.status', 1)
            ->where('wp.status', 1)
            ->where('wh.release_status', 1)
            ->where('wh.date', '<', $fromYmd)
            ->sum('wh.released_hour');

        $rows = DB::table('tasks as ts')
            ->join('weekly_timesheet_project_task_details as wt', 'wt.task_id', '=', 'ts.id')
            ->join('weekly_timesheet_project_task_hours as wh', 'wh.timesheet_project_task_id', '=', 'wt.id')
            ->join('task_status as tst', 'tst.id', '=', 'ts.status')
            ->join('weekly_timesheet as t', 't.id', '=', 'wt.timesheet_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('ts.project_id', $project)
            ->where('wh.status', 1)
            ->where('wt.status', 1)
            ->where('wh.release_status', 1)
            ->where('wh.date', '>=', $fromYmd)
            ->where('wh.date', '<=', $toYmd)
            ->where('wh.released_hour', '>', 0)
            ->orderBy('ts.id')
            ->orderBy('wh.date')
            ->select(
                'ts.id as task_id',
                'ts.title as task_title',
                'ts.due_date',
                'tst.title as task_status',
                'wh.date',
                'wh.released_hour as totalhour',
                'u.first_name as consultant'
            )
            ->get();

        $taskMap = [];
        foreach ($rows as $row) {
            if (!isset($taskMap[$row->task_id])) {
                $taskMap[$row->task_id] = [
                    'ticket_no' => '#' . str_pad($row->task_id, 5, '0', STR_PAD_LEFT),
                    'title' => $row->task_title,
                    'created_at' => date('d-M-Y', strtotime($row->due_date)),
                    'status' => $row->task_status,
                    'grouped_hours_map' => [],
                    'detailed_hours' => [],
                    'total_hours' => 0,
                ];
            }

            $monthKey = date('M-Y', strtotime($row->date));
            if (!isset($taskMap[$row->task_id]['grouped_hours_map'][$monthKey])) {
                $taskMap[$row->task_id]['grouped_hours_map'][$monthKey] = 0;
            }
            $taskMap[$row->task_id]['grouped_hours_map'][$monthKey] += (float) $row->totalhour;
            $taskMap[$row->task_id]['detailed_hours'][] = [
                'consultant' => $row->consultant,
                'date' => date('d-M-Y', strtotime($row->date)),
                'totalhr' => (float) $row->totalhour,
            ];
            $taskMap[$row->task_id]['total_hours'] += (float) $row->totalhour;
        }

        $taskDetails = [];
        foreach ($taskMap as $task) {
            $groupedHours = [];
            foreach ($task['grouped_hours_map'] as $month => $hours) {
                $groupedHours[] = [
                    'date' => $month,
                    'totalhr' => $hours,
                ];
            }
            unset($task['grouped_hours_map']);
            $task['grouped_hours'] = $groupedHours;
            $taskDetails[] = $task;
        }

        $projectHours = $projectData->hours ?? 0;
        $balanceCf = $projectHours - $utilizedUptoFromDate;
        $closingBalance = $balanceCf - $hoursUtilized;

        $pdf = Pdf::loadView('support-statement.print', [
            'customer' => $customerData,
            'project' => $projectData,
            'balance_cf' => $balanceCf,
            'hours_utilized' => $hoursUtilized,
            'closing_balance' => $closingBalance,
            'tasks' => $taskDetails,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'show_consultant' => $showConsultant,
            'pdf_mode' => true,
        ]);

        return $pdf->download('supportstatement.pdf');
    }
    
    /**
     * Get projects filtered by customer and/or project type
     */
    public function getProjectsByCustomer(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdmin = $user->inGroup(1);
            $isCustomerAdmin = $user->inGroup(3);

            $query = DB::table('projects as p')->select('p.id', 'p.project_id', 'p.title');

            if ($isAdmin) {
                if ($request->filled('customerid')) {
                    $query->where('p.client_id', $request->customerid);
                }
            } elseif ($isCustomerAdmin) {
                $clientIds = $user->getCustomerClientIds();
                $query->whereIn('p.client_id', $clientIds)->where('p.is_visible', 0);
                if ($request->filled('customerid')) {
                    $query->where('p.client_id', $request->customerid);
                }
            } else {
                $query->join('project_users as pu', 'pu.project_id', '=', 'p.id')
                    ->where('pu.user_id', $user->id);
                // For consultant flow, customer filter is optional and should not block if empty/invalid.
                if ($request->filled('customerid')) {
                    $query->where('p.client_id', $request->customerid);
                }
            }

            if ($request->filled('projecttype')) {
                $query->where('p.ptype', $request->projecttype);
            }

            $projects = $query
                ->groupBy('p.id', 'p.project_id', 'p.title', 'p.created')
                ->orderByDesc('p.created')
                ->get();
            
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
     * Get the parent company ID for a customer user.
     * Mirrors CI: Users_model::get_cuser_parent_company_id()
     */
    private function getCuserParentCompanyId($userId)
    {
        $user = User::find($userId);
        return ($user && $user->cuser_customer) ? $user->cuser_customer : null;
    }

    private function canAccessProject($user, int $projectId): bool
    {
        if ($user->inGroup(1)) {
            return true;
        }

        $project = DB::table('projects')->where('id', $projectId)->first();
        if (!$project) {
            return false;
        }

        if ($user->inGroup(3)) {
            $clientIds = $user->getCustomerClientIds();
            return in_array($project->client_id, $clientIds) && (int) $project->is_visible === 0;
        }

        return DB::table('project_users')
            ->where('project_id', $projectId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
