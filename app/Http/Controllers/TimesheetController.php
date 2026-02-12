<?php

namespace App\Http\Controllers;

use App\Models\WeeklyTimesheet;
use App\Models\TimesheetTask;
use App\Models\User;
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
            'page_title' => 'Timesheet - ' . company_name(),
            'current_user' => $user,
            'consultants' => $consultants,
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
                    'rows' => []
                ]);
            }
            
            // Bootstrap Table parameters (matching CodeIgniter)
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'ASC');
            $search = $request->input('search', '');
            
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
                $billableHours = $timesheet->billable_hours ?? 0;
                $nonBillableHours = $timesheet->non_billable_hours ?? 0;
                
                // Status determination (exact CodeIgniter logic)
                $status = 'Draft';
                $statusClass = 'draft-sts';
                
                if ($timesheet->submit_or_draft === 'submit') {
                    // Use pre-calculated counts from subqueries
                    $totalDetails = $timesheet->total_details ?? 0;
                    $approvedDetails = $timesheet->approved_details ?? 0;
                    $rejectedDetails = $timesheet->rejected_details ?? 0;
                    
                    // Determine status (exact CodeIgniter logic)
                    if ($rejectedDetails > 0) {
                        $status = 'Returned';
                        $statusClass = 'returned-sts';
                    } elseif ($approvedDetails < $totalDetails && $approvedDetails > 0) {
                        $status = 'In Review';
                        $statusClass = 'review-sts';
                    } elseif ($approvedDetails == $totalDetails && $totalDetails > 0) {
                        $status = 'Approved';
                        $statusClass = 'approved-sts';
                    } else {
                        $status = 'Submitted';
                        $statusClass = 'sub-sts';
                    }
                }
                
                $rows[] = [
                    'id' => $timesheet->id,
                    'timesheet_id' => 'T' . str_pad($timesheet->id, 5, '0', STR_PAD_LEFT),
                    'user' => $timesheet->user,
                    'work_week' => $timesheet->work_week ?? 'N/A',
                    'billable' => $billableHours ?? 0,
                    'non_billable' => $nonBillableHours ?? 0,
                    'status' => $status,
                    'status_class' => $statusClass,
                    'submit_or_draft' => $timesheet->submit_or_draft,
                ];
            }
            
            \Log::info('Timesheets query result', [
                'total' => $total,
                'rows_count' => count($rows)
            ]);
            
            // Return Bootstrap Table format (exact CodeIgniter format)
            return response()->json([
                'total' => $total,
                'rows' => $rows
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching timesheets: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'total' => 0,
                'rows' => []
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
        
        $data = [
            'page_title' => 'Create Timesheet - ' . company_name(),
            'current_user' => $user,
        ];
        
        return view('timesheets.create', $data);
    }
    
    /**
     * Store new timesheet
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            
            if ($user->inGroup(3)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'submit_or_draft' => 'required|in:draft,submit',
            ]);
            
            $startDate = date('Y-m-d', strtotime($request->start_date));
            $endDate = date('Y-m-d', strtotime($request->end_date));
            $workWeek = date('d-M-Y', strtotime($startDate)) . ' to ' . date('d-M-Y', strtotime($endDate));
            
            // Create timesheet
            $timesheet = WeeklyTimesheet::create([
                'user_id' => $user->inGroup(1) && $request->filled('user_id') ? $request->user_id : $user->id,
                'work_week' => $workWeek,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'submit_or_draft' => $request->submit_or_draft,
                'created' => date('Y-m-d'),
                'status' => 1, // Active status like old project
            ]);
            
            return response()->json([
                'error' => false,
                'message' => 'Timesheet created successfully',
                'timesheet' => $timesheet
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error creating timesheet: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error creating timesheet: ' . $e->getMessage()
            ], 500);
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
            
            // Only allow deleting draft timesheets (matching old project logic)
            if ($timesheet->submit_or_draft !== 'draft') {
                return response()->json(['error' => true, 'message' => 'Only draft timesheets can be deleted'], 403);
            }
            
            // Soft delete by setting status to 0
            $timesheet->update(['status' => 0]);
            
            return response()->json([
                'error' => false,
                'message' => 'Timesheet deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error deleting timesheet: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error deleting timesheet'
            ], 500);
        }
    }
}
