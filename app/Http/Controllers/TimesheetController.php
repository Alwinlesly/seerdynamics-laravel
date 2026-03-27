<?php

namespace App\Http\Controllers;

use App\Models\WeeklyTimesheet;
use App\Models\TimesheetTask;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimesheetController extends Controller
{
    /**
     * Display timesheet listing page
     */
    public function index()
    {
        $user = auth()->user();
        
        // Only admins and consultants can access (not customers - group 3)
        if ($user->inGroup(3)) {
            abort(403, 'Access Denied');
        }
        
        // Get consultants list for filter dropdown (admins and consultants only)
        $consultants = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->whereIn('users_groups.group_id', [1, 2])
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->get();
        
        $data = [
            'page_title'   => 'Timesheet - ' . company_name(),
            'current_user' => $user,
            'consultants'  => $consultants,
        ];
        
        return view('timesheets.index', $data);
    }
    
    /**
     * Get timesheets list via AJAX - matching exact CodeIgniter logic
     */
    public function getTimesheets(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Only admins and consultants
            if ($user->inGroup(3)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            // Check if table exists
            $tableExists = DB::select("SHOW TABLES LIKE 'weekly_timesheet'");
            
            \Log::info('Timesheet table check', ['exists' => !empty($tableExists)]);
            
            if (empty($tableExists)) {
                \Log::warning('weekly_timesheet table does not exist');
                return response()->json([
                    'total' => 0,
                    'rows'  => []
                ]);
            }
            
            // Bootstrap Table parameters (matching CodeIgniter)
            $offset = $request->input('offset', 0);
            $limit  = $request->input('limit', 10);
            $sort   = $request->input('sort', 'id');
            $order  = strtoupper($request->input('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
            $search = $request->input('search', '');

            $allowedSorts = ['id', 'work_week', 'created'];
            if (!in_array($sort, $allowedSorts, true)) {
                $sort = 'id';
            }
            
            // Base query with user join and calculated fields using subqueries
            $query = WeeklyTimesheet::query()
                ->join('users as u', 'u.id', '=', 'weekly_timesheet.user_id')
                ->select(
                    'weekly_timesheet.*', 
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user"),
                    // Billable hours subquery
                    DB::raw("(
                        SELECT COALESCE(SUM(wh.hours), 0)
                        FROM weekly_timesheet_project_task_hours wh
                        JOIN weekly_timesheet_project_task_details wp ON wh.timesheet_project_task_id = wp.id
                        WHERE wp.timesheet_id = weekly_timesheet.id
                        AND wh.status = 1
                        AND wp.status = 1
                        AND wp.billable = 1
                    ) as billable_hours"),
                    // Non-billable hours subquery
                    DB::raw("(
                        SELECT COALESCE(SUM(wh.hours), 0)
                        FROM weekly_timesheet_project_task_hours wh
                        JOIN weekly_timesheet_project_task_details wp ON wh.timesheet_project_task_id = wp.id
                        WHERE wp.timesheet_id = weekly_timesheet.id
                        AND wh.status = 1
                        AND wp.status = 1
                        AND wp.billable = 0
                    ) as non_billable_hours"),
                    // Total details count
                    DB::raw("(
                        SELECT COUNT(*)
                        FROM weekly_timesheet_project_task_details
                        WHERE timesheet_id = weekly_timesheet.id
                        AND status = 1
                    ) as total_details"),
                    // Approved details count
                    DB::raw("(
                        SELECT COUNT(*)
                        FROM weekly_timesheet_project_task_details
                        WHERE timesheet_id = weekly_timesheet.id
                        AND status = 1
                        AND approved_status = 1
                    ) as approved_details"),
                    // Rejected details count
                    DB::raw("(
                        SELECT COUNT(*)
                        FROM weekly_timesheet_project_task_details
                        WHERE timesheet_id = weekly_timesheet.id
                        AND status = 1
                        AND approved_status = 2
                    ) as rejected_details")
                )
                ->where('weekly_timesheet.status', 1);
            
            // Role-based filtering (exact CodeIgniter logic)
            if ($user->inGroup(1)) { // Admin
                if ($request->filled('user_id')) {
                    $query->where('weekly_timesheet.user_id', $request->user_id);
                }
            } else { // Consultant
                $query->where('weekly_timesheet.user_id', $user->id);
            }
            
            // Status filter
            if ($request->filled('status')) {
                $query->where('weekly_timesheet.submit_or_draft', $request->status);
            }
            
            // Project filter — /projects/{id} Timesheet button passes ?project={id}
            if ($request->filled('project')) {
                $projectId = $request->project;
                $query->whereExists(function ($sub) use ($projectId) {
                    $sub->select(DB::raw(1))
                        ->from('weekly_timesheet_project_task_details as wpd')
                        ->whereColumn('wpd.timesheet_id', 'weekly_timesheet.id')
                        ->where('wpd.project_id', $projectId)
                        ->where('wpd.status', 1);
                });
            }
            
            // Search filter (exact CodeIgniter logic)
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('u.first_name', 'like', "%{$search}%")
                      ->orWhere('u.last_name', 'like', "%{$search}%")
                      ->orWhere('weekly_timesheet.work_week', 'like', "%{$search}%")
                      ->orWhere('weekly_timesheet.id', 'like', "%{$search}%");
                });
            }
            
            // Get total count
            $total = $query->count();
            
            // Get paginated results
            $timesheets = $query->orderBy("weekly_timesheet.{$sort}", $order)
                ->skip($offset)
                ->take($limit)
                ->get();
            
            $rows = [];
            
            foreach ($timesheets as $timesheet) {
                // Use pre-calculated values from subqueries (no additional DB queries!)
                $billableHours    = $timesheet->billable_hours ?? 0;
                $nonBillableHours = $timesheet->non_billable_hours ?? 0;
                $isAdmin          = $user->inGroup(1);
                
                // Status determination (exact CodeIgniter logic)
                $status      = 'Draft';
                $statusClass = 'draft-sts';
                
                if ($timesheet->submit_or_draft === 'submit') {
                    // Use pre-calculated counts from subqueries
                    $totalDetails    = $timesheet->total_details ?? 0;
                    $approvedDetails = $timesheet->approved_details ?? 0;
                    $rejectedDetails = $timesheet->rejected_details ?? 0;
                    
                    // Determine status (exact CodeIgniter logic)
                    if ($rejectedDetails > 0) {
                        $status      = 'Returned';
                        $statusClass = 'returned-sts';
                    } elseif ($approvedDetails < $totalDetails && $approvedDetails > 0) {
                        $status      = 'In Review';
                        $statusClass = 'review-sts';
                    } elseif ($approvedDetails == $totalDetails && $totalDetails > 0) {
                        $status      = 'Approved';
                        $statusClass = 'approved-sts';
                    } else {
                        $status      = 'Submitted';
                        $statusClass = 'sub-sts';
                    }
                }

                // Action permissions (match existing CI logic)
                // Always: view
                // Draft: edit + delete (admin and consultant-owner)
                // Submit:
                //   - Returned: edit + delete
                //   - No approvals yet: admin can edit + delete
                $canEdit = false;
                $canDelete = false;
                if ($timesheet->submit_or_draft === 'draft') {
                    $canEdit = true;
                    $canDelete = true;
                } elseif ($timesheet->submit_or_draft === 'submit') {
                    $approvedDetails = (int) ($timesheet->approved_details ?? 0);
                    $rejectedDetails = (int) ($timesheet->rejected_details ?? 0);
                    if ($rejectedDetails > 0) {
                        $canEdit = true;
                        $canDelete = true;
                    } elseif ($approvedDetails === 0 && $isAdmin) {
                        $canEdit = true;
                        $canDelete = true;
                    }
                }
                
                $rows[] = [
                    'id'             => $timesheet->id,
                    'timesheet_id'   => 'T' . str_pad($timesheet->id, 5, '0', STR_PAD_LEFT),
                    'user'           => $timesheet->user,
                    'work_week'      => $timesheet->work_week ?? 'N/A',
                    'billable'       => $billableHours ?? 0,
                    'non_billable'   => $nonBillableHours ?? 0,
                    'status'         => $status,
                    'status_class'   => $statusClass,
                    'submit_or_draft'=> $timesheet->submit_or_draft,
                    'can_edit'       => $canEdit,
                    'can_delete'     => $canDelete,
                ];
            }
            
            \Log::info('Timesheets query result', [
                'total'      => $total,
                'rows_count' => count($rows)
            ]);
            
            // Return Bootstrap Table format (exact CodeIgniter format)
            return response()->json([
                'total' => $total,
                'rows'  => $rows
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching timesheets: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'total' => 0,
                'rows'  => []
            ]);
        }
    }
    
    /**
     * Show create timesheet form
     */
    public function create()
    {
        $user = auth()->user();
        
        if ($user->inGroup(3)) {
            abort(403, 'Access Denied');
        }
        
        // Pass system users (consultants) for admin dropdown
        $systemUsers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->whereIn('users_groups.group_id', [1, 2])
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->get();
        
        $data = [
            'page_title'   => 'Create Timesheet - ' . company_name(),
            'current_user' => $user,
            'system_users' => $systemUsers,
        ];
        
        return view('timesheets.create', $data);
    }
    
    /**
     * Store new timesheet - mirrors exact CodeIgniter logic
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            
            if ($user->inGroup(3)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            // Validate required header fields
            $rules = [
                'start_date'       => 'required',
                'end_date'         => 'required',
                'submit_or_draft'  => 'required|in:draft,submit',
            ];
            if ($user->inGroup(1)) {
                $rules['user_id'] = 'required';
            }
            $request->validate($rules);
            
            // Parse dates (input may be dd-mm-yyyy or Y-m-d)
            $startDate = $this->parseFlexDate($request->start_date);
            $endDate   = $this->parseFlexDate($request->end_date);
            $workWeek  = $request->start_date . '-' . $request->end_date;

            // Insert header record
            $insertData = [
                'user_id'         => $user->inGroup(1) && $request->filled('user_id') ? $request->user_id : $user->id,
                'work_week'       => $workWeek,
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'created'         => date('Y-m-d'),
                'submit_or_draft' => $request->submit_or_draft,
                'status'          => 1,
            ];

            $timesheetId = DB::table('weekly_timesheet')->insertGetId($insertData);
            
            // Process row entries (exact CodeIgniter logic)
            $rowCount  = (int) $request->input('rowindex', 1);
            $colsCount = (int) $request->input('colindex', 1);
            
            for ($i = 1; $i <= $rowCount; $i++) {
                $pid = $request->input('project_id_' . $i);
                
                if (isset($pid) && $pid !== null && $pid !== '') {
                    // Insert project task detail
                    $detailId = DB::table('weekly_timesheet_project_task_details')->insertGetId([
                        'timesheet_id' => $timesheetId,
                        'project_id'   => $pid,
                        'customer_id'  => $request->input('customer_' . $i),
                        'task_id'      => $request->input('task_id_' . $i),
                        'billable'     => $request->input('billable_not_' . $i, 1),
                        'status'       => 1,
                    ]);
                    
                    // Insert hours for each day column
                    for ($j = 1; $j <= $colsCount; $j++) {
                        DB::table('weekly_timesheet_project_task_hours')->insert([
                            'timesheet_project_task_id' => $detailId,
                            'date'   => $request->input('date_' . $j),
                            'day'    => $request->input('day_' . $j),
                            'hours'  => $request->input('totalhour_' . $i . '_' . $j) ?: 0,
                            'note'   => $request->input('note_' . $i . '_' . $j),
                            'status' => 1,
                        ]);
                    }
                }
            }
            
            return response()->json([
                'error'   => false,
                'message' => 'Timesheet ' . ($request->submit_or_draft === 'submit' ? 'submitted' : 'saved as draft') . ' successfully.',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error creating timesheet: ' . $e->getMessage());
            return response()->json([
                'error'   => true,
                'message' => 'Error creating timesheet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show read-only view of a timesheet
     */
    public function show($id)
    {
        $user = auth()->user();

        if ($user->inGroup(3)) {
            abort(403, 'Access Denied');
        }

        $timesheet = DB::table('weekly_timesheet')->where('id', $id)->where('status', 1)->first();
        if (!$timesheet) abort(404);

        // Non-admins can only view their own
        if (!$user->inGroup(1) && $timesheet->user_id != $user->id) {
            abort(403, 'Access Denied');
        }

        $timesheetProjects = DB::table('weekly_timesheet_project_task_details as wd')
            ->join('projects as p', 'p.id', '=', 'wd.project_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'wd.customer_id')
            ->leftJoin('tasks as t', 't.id', '=', 'wd.task_id')
            ->where('wd.timesheet_id', $id)
            ->where('wd.status', 1)
            ->select('wd.*', 'p.project_id as project_code', 'p.title as project_title',
                     'cu.company as customer_company', 't.title as task_title')
            ->get();

        // Attach hours per project row
        foreach ($timesheetProjects as $row) {
            $row->hours = DB::table('weekly_timesheet_project_task_hours')
                ->where('timesheet_project_task_id', $row->id)
                ->where('status', 1)
                ->orderBy('date')
                ->get();
        }

        // Build ordered dates
        $dates = [];
        $current = strtotime($timesheet->start_date);
        $endTs   = strtotime($timesheet->end_date);
        while ($current <= $endTs) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        $owners = DB::table('users')->where('id', $timesheet->user_id)->first();

        $data = [
            'page_title'       => 'View Timesheet - ' . company_name(),
            'current_user'     => $user,
            'timesheet'        => $timesheet,
            'timesheetprojects'=> $timesheetProjects,
            'dates'            => $dates,
            'owner'            => $owners,
        ];

        return view('timesheets.show', $data);
    }

    /**
     * Show edit form for an existing timesheet
     */
    public function edit($id)
    {
        $user = auth()->user();

        if ($user->inGroup(3)) abort(403, 'Access Denied');

        $timesheet = DB::table('weekly_timesheet')->where('id', $id)->where('status', 1)->first();
        if (!$timesheet) abort(404);

        // Only admins or the owner can edit
        if (!$user->inGroup(1) && $timesheet->user_id != $user->id) {
            abort(403, 'Access Denied');
        }

        if (!$this->canEditOrDeleteTimesheet($timesheet, $user)) {
            abort(403, 'Access Denied');
        }

        $timesheetProjects = DB::table('weekly_timesheet_project_task_details as wd')
            ->where('wd.timesheet_id', $id)
            ->where('wd.status', 1)
            ->get();

        foreach ($timesheetProjects as $row) {
            $row->hours = DB::table('weekly_timesheet_project_task_hours')
                ->where('timesheet_project_task_id', $row->id)
                ->where('status', 1)
                ->orderBy('date')
                ->get();
        }

        // Build ordered dates
        $dates = [];
        $current = strtotime($timesheet->start_date);
        $endTs   = strtotime($timesheet->end_date);
        while ($current <= $endTs) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        $systemUsers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->whereIn('users_groups.group_id', [1, 2])
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->get();

        $data = [
            'page_title'        => 'Edit Timesheet - ' . company_name(),
            'current_user'      => $user,
            'timesheet'         => $timesheet,
            'timesheetprojects' => $timesheetProjects,
            'dates'             => $dates,
            'system_users'      => $systemUsers,
        ];

        return view('timesheets.edit', $data);
    }

    /**
     * Update an existing timesheet — mirrors CI edit_weekly_timesheet() logic
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();

            if ($user->inGroup(3)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $timesheet = DB::table('weekly_timesheet')->where('id', $id)->where('status', 1)->first();
            if (!$timesheet) {
                return response()->json(['error' => true, 'message' => 'Timesheet not found'], 404);
            }

            if (!$user->inGroup(1) && $timesheet->user_id != $user->id) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            if (!$this->canEditOrDeleteTimesheet($timesheet, $user)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $startDate = $this->parseFlexDate($request->start_date);
            $endDate   = $this->parseFlexDate($request->end_date);
            $workWeek  = $request->start_date . '-' . $request->end_date;

            // Update the header row
            DB::table('weekly_timesheet')->where('id', $id)->update([
                'user_id'         => $user->inGroup(1) && $request->filled('user_id') ? $request->user_id : $timesheet->user_id,
                'work_week'       => $workWeek,
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'submit_or_draft' => $request->submit_or_draft,
            ]);

            // Soft-delete all existing project detail rows (then re-insert)
            $existingDetails = DB::table('weekly_timesheet_project_task_details')
                ->where('timesheet_id', $id)->where('status', 1)->pluck('id');

            foreach ($existingDetails as $detailId) {
                DB::table('weekly_timesheet_project_task_hours')
                    ->where('timesheet_project_task_id', $detailId)
                    ->update(['status' => 0]);
            }
            DB::table('weekly_timesheet_project_task_details')
                ->where('timesheet_id', $id)
                ->update(['status' => 0]);

            // Re-insert submitted rows
            $rowCount  = (int) $request->input('rowindex', 1);
            $colsCount = (int) $request->input('colindex', 1);

            for ($i = 1; $i <= $rowCount; $i++) {
                $pid = $request->input('project_id_' . $i);
                if (!empty($pid)) {
                    $detailId = DB::table('weekly_timesheet_project_task_details')->insertGetId([
                        'timesheet_id' => $id,
                        'project_id'   => $pid,
                        'customer_id'  => $request->input('customer_' . $i),
                        'task_id'      => $request->input('task_id_' . $i),
                        'billable'     => $request->input('billable_not_' . $i, 1),
                        'status'       => 1,
                    ]);

                    for ($j = 1; $j <= $colsCount; $j++) {
                        DB::table('weekly_timesheet_project_task_hours')->insert([
                            'timesheet_project_task_id' => $detailId,
                            'date'   => $request->input('date_' . $j),
                            'day'    => $request->input('day_' . $j),
                            'hours'  => $request->input('totalhour_' . $i . '_' . $j) ?: 0,
                            'note'   => $request->input('note_' . $i . '_' . $j),
                            'status' => 1,
                        ]);
                    }
                }
            }

            return response()->json([
                'error'   => false,
                'message' => 'Timesheet updated successfully.',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating timesheet: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Error updating timesheet.'], 500);
        }
    }

    /**
     * Delete timesheet
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            if ($user->inGroup(3)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            $timesheet = WeeklyTimesheet::findOrFail($id);
            
            // Non-admins can only delete their own timesheets
            if (!$user->inGroup(1) && $timesheet->user_id != $user->id) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            if (!$this->canEditOrDeleteTimesheet($timesheet, $user)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            // Soft delete by setting status to 0
            $timesheet->update(['status' => 0]);
            
            return response()->json([
                'error'   => false,
                'message' => 'Timesheet deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error deleting timesheet: ' . $e->getMessage());
            return response()->json([
                'error'   => true,
                'message' => 'Error deleting timesheet'
            ], 500);
        }
    }

    // -----------------------------------------------------------------------
    // AJAX helper endpoints (mirror CodeIgniter Projects controller methods)
    // -----------------------------------------------------------------------

    /**
     * GET /timesheet/customers
     * Returns all customer users (group 3) - mirrors Projects::getcustomers()
     */
    public function getCustomers()
    {
        $clients = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->where('users_groups.group_id', 3)
            ->where('users.active', 1)
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.company')
            ->get();

        return response()->json(['system_clients' => $clients]);
    }

    /**
     * POST /timesheet/projects-by-customer
     * Returns projects for a given customer - mirrors Projects::getProjectListByCustomer()
     */
    public function getProjectsByCustomer(Request $request)
    {
        $customerId  = $request->input('customerid');
        $projectType = $request->input('projecttype');

        $query = Project::where('client_id', $customerId)
            ->where('is_visible', 0); // visible == not deleted

        if ($projectType) {
            $query->where('ptype', $projectType);
        }

        $projects = $query->select('id', 'project_id', 'title')->get();

        return response()->json(['projects' => $projects]);
    }

    /**
     * POST /timesheet/tasks-by-project
     * Returns tasks (tickets) for a given project - mirrors Projects::get_tasks_by_project_id_timesheet()
     */
    public function getTasksByProject(Request $request)
    {
        $projectId = $request->input('project_id');

        $tasks = Task::where('project_id', $projectId)
            ->select('id', 'title')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    /**
     * POST /timesheet/day-totals
     * Returns existing hour totals per day for a consultant/date-range
     * mirrors Projects::getdaytotalhr()
     */
    public function getDayTotalHours(Request $request)
    {
        $startDate    = $request->input('startdate');
        $endDate      = $request->input('enddate');
        $consultantId = $request->input('consultant_id');

        $rows = DB::table('weekly_timesheet_project_task_hours as wh')
            ->join('weekly_timesheet_project_task_details as wd', 'wh.timesheet_project_task_id', '=', 'wd.id')
            ->join('weekly_timesheet as wt', 'wd.timesheet_id', '=', 'wt.id')
            ->where('wt.user_id', $consultantId)
            ->where('wt.status', 1)
            ->where('wd.status', 1)
            ->where('wh.status', 1)
            ->whereBetween('wh.date', [$startDate, $endDate])
            ->select('wh.date', DB::raw('SUM(wh.hours) as totalhr'))
            ->groupBy('wh.date')
            ->orderBy('wh.date')
            ->get();

        return response()->json(['daystotal' => $rows]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Parse a date that could be dd-mm-yyyy or Y-m-d
     */
    private function parseFlexDate($dateStr)
    {
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateStr)) {
            // dd-mm-yyyy
            [$d, $m, $y] = explode('-', $dateStr);
            return "$y-$m-$d";
        }
        // Assume Y-m-d or strtotime compatible
        return date('Y-m-d', strtotime($dateStr));
    }

    /**
     * Edit/Delete permission parity with legacy CI logic for weekly timesheet.
     */
    private function canEditOrDeleteTimesheet($timesheet, $user): bool
    {
        if (!$timesheet || (int) ($timesheet->status ?? 0) !== 1) {
            return false;
        }

        // Non-admin can only manage own rows
        if (!$user->inGroup(1) && (int) $timesheet->user_id !== (int) $user->id) {
            return false;
        }

        if ($timesheet->submit_or_draft === 'draft') {
            return true;
        }

        if ($timesheet->submit_or_draft !== 'submit') {
            return false;
        }

        $baseQuery = DB::table('weekly_timesheet_project_task_details')
            ->where('timesheet_id', $timesheet->id)
            ->where('status', 1);

        $approvedCount = (clone $baseQuery)->where('approved_status', 1)->count();
        $rejectedCount = (clone $baseQuery)->where('approved_status', 2)->count();

        // Returned -> editable/deletable
        if ($rejectedCount > 0) {
            return true;
        }

        // Submitted with no approvals yet -> admin only
        if ($approvedCount === 0 && $user->inGroup(1)) {
            return true;
        }

        return false;
    }
}
