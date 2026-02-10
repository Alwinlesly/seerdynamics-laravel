<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\Priority;
use App\Models\IssueType;
use App\Models\Project;
use App\Models\User;
use App\Models\Timesheet;
use App\Models\TaskEstimate;
use App\Models\Message;
use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    /**
     * Display tasks list
     */
    public function index()
    {
        $user = auth()->user();
        
        $data = [
            'page_title' => 'Tasks - ' . company_name(),
            'current_user' => $user,
        ];
        
        // Get task statuses for filter
        $data['task_statuses'] = TaskStatus::all();
        
        // Get priorities for filter
        $data['priorities'] = Priority::all();
        
        // Get issue types for filter
        $data['issue_types'] = IssueType::all();
        
        // Get projects for filter (based on user role)
        if ($user->inGroup(3)) { // Customer
            $data['projects'] = Project::where('client_id', $user->id)->get();
        } elseif (!$user->inGroup(1)) { // Not admin
            $data['projects'] = Project::whereHas('users', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
        } else { // Admin
            $data['projects'] = Project::all();
        }
        
        // Get customers for filter (admins only)
        if ($user->inGroup(1)) {
            $data['customers'] = User::whereHas('groups', function($q) {
                $q->where('groups.id', 3); // Customer group ID
            })->get();
        }
        
        return view('tasks.index', $data);
    }
    
    /**
     * Get tasks via AJAX
     */
    public function getTasks(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Start with basic query - NO relationships
            $query = Task::query();
            
            // Role-based filtering
            if ($user->inGroup(3)) { // Customer
                $query->whereHas('project', function($q) use ($user) {
                    $q->where('client_id', $user->id);
                });
            } elseif (!$user->inGroup(1)) { // Not admin - show assigned tasks
                $query->whereHas('users', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            
            // Search filter
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')
                      ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            }
            
            // Project filter
            if ($request->project) {
                $query->where('project_id', $request->project);
            }
            
            // Status filter - compare by ID not title
            if ($request->status) {
                $statusRecord = TaskStatus::where('title', $request->status)->first();
                if ($statusRecord) {
                    $query->where('status', $statusRecord->id);
                }
            }
            
            // Priority filter
            if ($request->priority) {
                $query->where('priority', $request->priority);
            }
            
            // Customer filter
            if ($request->customer) {
                $query->whereHas('project', function($q) use ($request) {
                    $q->where('client_id', $request->customer);
                });
            }
            
            //Sorting
            $allowedSorts = ['id', 'created', 'title'];
            $sort = $request->sort ?? 'id';
            if (!in_array($sort, $allowedSorts)) {
                $sort = 'id';
            }
            $query->orderBy($sort, 'desc');
            
            $limit = $request->limit ?? 20;
            $offset = $request->offset ?? 0;
            
            $total = $query->count();
            $tasks = $query->skip($offset)->take($limit)->get();
            
            $rows = [];
            foreach ($tasks as $task) {
                // Get project name manually
                $projectName = '';
                if ($task->project_id) {
                    $project = \App\Models\Project::find($task->project_id);
                    $projectName = $project ? ($project->project_id . ' ' . $project->title) : '';
                }
                
                // Get customer manually
                $customerName = '';
                if ($task->project_id) {
                    $project = \App\Models\Project::find($task->project_id);
                    if ($project && $project->client_id) {
                        $customer = \App\Models\User::find($project->client_id);
                        $customerName = $customer ? ($customer->company ?? $customer->first_name) : '';
                    }
                }
                
                // Get status
                $statusName = 'Not Started';
                if ($task->status) {
                    $status = TaskStatus::find($task->status);
                    $statusName = $status ? $status->title : 'Not Started';
                }
                
                // Get priority
                $priorityName = '';
                if ($task->priority) {
                    $priority = \App\Models\Priority::find($task->priority);
                    $priorityName = $priority ? $priority->title : '';
                }
                
                // Get creator
                $creatorName = '';
                if ($task->created_by) {
                    $creator = \App\Models\User::find($task->created_by);
                    $creatorName = $creator ? ($creator->first_name . ' ' . $creator->last_name) : '';
                }
                
                $rows[] = [
                    'id' => $task->id,
                    'ticket_id' => $task->ticket_id ?? '#' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                    'title' => $task->title ?? '',
                    'project' => $projectName,
                    'customer' => $customerName,
                    'estimate' => $task->estimate ?? 0,
                    'priority' => $priorityName,
                    'priority_class' => strtolower(str_replace(' ', '-', $priorityName)),
                    'created_by' => $creatorName,
                    'created_date' => $task->created ? date('d-M-Y', strtotime($task->created)) : '',
                    'status' => $statusName,
                ];
            }
            
            return response()->json([
                'error' => false,
                'total' => $total,
                'rows' => $rows
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching tasks: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => true,
                'message' => 'Error loading tasks: ' . $e->getMessage(),
                'details' => $e->getTraceAsString(),
                'total' => 0,
                'rows' => []
            ], 500);
        }
    }
    
    /**
     * Show task details
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            
            // Load task with only essential relationships first
            $task = Task::with([
                'project.client', 
                'taskStatus', 
                'taskPriority',
                'issueType',
                'users', 
                'creator'
            ])->findOrFail($id);
            
            // Try to load comments if table exists
            if (\Schema::hasTable('task_comments')) {
                try {
                    $task->load('comments.user');
                } catch (\Exception $e) {
                    \Log::warning('Could not load comments: ' . $e->getMessage());
                }
            }
            
            // Try to load timesheets if table exists (check both naming conventions)
            if (\Schema::hasTable('timesheets') || \Schema::hasTable('timesheet')) {
                try {
                    $task->load('timesheets.user');
                } catch (\Exception $e) {
                    \Log::warning('Could not load timesheets: ' . $e->getMessage());
                }
            }
            
            // Check access
            if ($user->inGroup(3) && $task->project && $task->project->client_id != $user->id) {
                if (request()->ajax()) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
                abort(403, 'Access Denied');
            }

            // Return partial view for AJAX requests (modal)
            if (request()->ajax()) {
                $html = view('tasks.partials.detail-content', ['task' => $task])->render();
                return response()->json([
                    'error' => false,
                    'html' => $html
                ]);
            }

            // Return full page view
            $data = [
                'page_title' => 'Task Details - ' . company_name(),
                'current_user' => $user,
                'task' => $task,
            ];

            return view('tasks.show', $data);
            
        } catch (\Exception $e) {
            \Log::error('Error loading task details: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if (request()->ajax()) {
                return response()->json([
                    'error' => true, 
                    'message' => 'Error loading task: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Error loading task details');
        }
    }
    
    /**
     * Get task data for editing
     */
    public function edit($id)
    {
        try {
            $user = auth()->user();
            $task = Task::with(['taskStatus', 'users', 'taskPriority', 'issueType'])->findOrFail($id);
            
            // Check access
            if ($user->inGroup(3) && $task->project && $task->project->client_id != $user->id) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            if (!$user->inGroup(1) && !$user->inGroup(3)) {
                if (!$task->users->contains('id', $user->id)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
            }
            
            $taskData = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'project_id' => $task->project_id,
                'issue_type' => $task->issue_type,
                'service' => $task->service,
                'priority' => $task->priority,
                'due_date' => $task->due_date ? date('Y-m-d', strtotime($task->due_date)) : '',
                'status_title' => $task->taskStatus->title ?? '',
                'attachment' => $task->attachment,
                'additional_mail' => $task->additional_mail,
                'assigned_consultants' => $task->users->pluck('id')->toArray(),
            ];
            
            return response()->json([
                'error' => false,
                'task' => $taskData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading task for edit: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error loading task data'
            ], 500);
        }
    }
    
    /**
     * Store new task
     */
    public function store(Request $request)
    {
        if (!auth()->user()->inGroup(1) && !permissions('task_create')) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'issue_type_id' => 'nullable|exists:issue_types,id',
            'priority_id' => 'nullable|exists:priorities,id',
        ]);
        
        try {
            // Get status ID from title
            $status = TaskStatus::where('title', $request->status)->first();
            $validated['status'] = $status ? $status->id : 1;
            
            // Get priority ID from request or use default
            $validated['priority'] = $request->priority_id ?? 1;
            
            // Get issue type
            $validated['issue_type'] = $request->issue_type_id ?? null;
            
            $validated['service'] = $request->service;
            $validated['additional_mail'] = $request->additional_mail;
            $validated['created_by'] = auth()->id();
            $validated['created'] = now();
            $validated['due_date'] = $request->issue_date;
            
            // Generate ticket ID
            $lastTask = Task::orderBy('id', 'desc')->first();
            $nextId = $lastTask ? $lastTask->id + 1 : 1;
            $validated['ticket_id'] = '#' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            
            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/uploads/tasks'), $filename);
                $validated['attachment'] = 'assets/uploads/tasks/' . $filename;
            }
            
            $task = Task::create($validated);
            
            // Assign consultants
            if ($request->assigned_consultants) {
                $consultantIds = array_filter(explode(',', $request->assigned_consultants));
                $task->users()->sync($consultantIds);
            }
            
            return response()->json([
                'error' => false,
                'message' => 'Task created successfully',
                'task_id' => $task->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error creating task: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error creating task: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update task
     */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        
        if (!auth()->user()->inGroup(1) && !permissions('task_edit')) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'issue_type_id' => 'nullable|exists:issue_types,id',
            'priority_id' => 'nullable|exists:priorities,id',
        ]);
        
        try {
            // Get status ID from title
            $status = TaskStatus::where('title', $request->status)->first();
            $validated['status'] = $status ? $status->id : $task->status;
            
            // Update priority
            $validated['priority'] = $request->priority_id ?? $task->priority;
            
            // Update issue type
            $validated['issue_type'] = $request->issue_type_id ?? $task->issue_type;
            
            $validated['service'] = $request->service;
            $validated['additional_mail'] = $request->additional_mail;
            $validated['due_date'] = $request->issue_date;
            
            // Handle file upload
            if ($request->hasFile('attachment')) {
                // Delete old file
                if ($task->attachment && file_exists(public_path($task->attachment))) {
                    unlink(public_path($task->attachment));
                }
                
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/uploads/tasks'), $filename);
                $validated['attachment'] = 'assets/uploads/tasks/' . $filename;
            }
            
            $task->update($validated);
            
            // Update consultants
            if ($request->has('assigned_consultants')) {
                $consultantIds = array_filter(explode(',', $request->assigned_consultants));
                $task->users()->sync($consultantIds);
            }
            
            return response()->json([
                'error' => false,
                'message' => 'Task updated successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error updating task: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error updating task: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete task
     */
    public function destroy($id)
    {
        if (!auth()->user()->inGroup(1) && !permissions('task_delete')) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }
        
        try {
            $task = Task::findOrFail($id);
            
            // Delete attachment
            if ($task->attachment && file_exists(public_path($task->attachment))) {
                unlink(public_path($task->attachment));
            }
            
            $task->delete();
            
            return response()->json([
                'error' => false,
                'message' => 'Task deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error deleting task: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error deleting task'
            ], 500);
        }
    }
    
    /**
     * Export tasks to Excel/CSV
     */
    public function export(Request $request)
    {
        try {
            $user = auth()->user();
            $query = Task::with(['project.client', 'taskStatus', 'taskPriority', 'creator']);
            
            // Apply same filters as getTasks
            if ($user->inGroup(3)) {
                $query->whereHas('project', function($q) use ($user) {
                    $q->where('client_id', $user->id);
                });
            } elseif (!$user->inGroup(1)) {
                $query->whereHas('users', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            
            // Apply filters from request
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('ticket_id', 'like', '%'.$request->search.'%')
                      ->orWhere('title', 'like', '%'.$request->search.'%')
                      ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            }
            
            if ($request->project) {
                $query->where('project_id', $request->project);
            }
            
            if ($request->status) {
                $query->whereHas('taskStatus', function($q) use ($request) {
                    $q->where('title', $request->status);
                });
            }
            
            if ($request->customer) {
                $query->whereHas('project', function($q) use ($request) {
                    $q->where('client_id', $request->customer);
                });
            }
            
            if ($request->priority) {
                $query->where('priority', $request->priority);
            }
            
            $tasks = $query->orderBy('id', 'desc')->get();
            
            // Create CSV content
            $filename = 'tasks_' . date('Y-m-d_H-i-s') . '.csv';
            $handle = fopen('php://temp', 'r+');
            
            // Add UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($handle, [
                'Ticket ID',
                'Title',
                'Description',
                'Project',
                'Customer',
                'Status',
                'Priority',
                'Estimate',
                'Created By',
                'Created Date',
                'Due Date'
            ]);
            
            // Data rows
            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->ticket_id ?? '#' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                    $task->title,
                    $task->description,
                    $task->project ? $task->project->project_id . ' ' . $task->project->title : '',
                    $task->project && $task->project->client ? 
                        ($task->project->client->company ?? $task->project->client->first_name) : '',
                    $task->taskStatus->title ?? 'Not Started',
                    $task->taskPriority->title ?? '',
                    $task->estimate ?? 0,
                    $task->creator ? $task->creator->first_name . ' ' . $task->creator->last_name : '',
                    $task->created ? date('d-M-Y', strtotime($task->created)) : '',
                    $task->due_date ? date('d-M-Y', strtotime($task->due_date)) : '',
                ]);
            }
            
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);
            
            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            \Log::error('Error exporting tasks: ' . $e->getMessage());
            return back()->with('error', 'Error exporting tasks');
        }
    }
    
    /**
     * Start timer for a task
     */
    public function startTimer($id)
    {
        try {
            $user = auth()->user();
            $task = Task::findOrFail($id);
            
            // Check if user has access to this task
            if (!$user->inGroup(1) && !$task->users->contains('id', $user->id)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            // Check if timesheet table exists
            $tableName = \Schema::hasTable('timesheet') ? 'timesheet' : 'timesheets';
            
            // If neither table exists, create timesheets
            if (!(\Schema::hasTable('timesheet') || \Schema::hasTable('timesheets'))) {
                \Schema::create('timesheets', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('task_id');
                    $table->unsignedBigInteger('user_id');
                    $table->dateTime('start_time');
                    $table->dateTime('end_time')->nullable();
                    $table->decimal('total_hours', 8, 2)->nullable();
                    $table->timestamps();
                });
                $tableName = 'timesheets';
            }
            
            // Check columns based on table
            $startColumn = $tableName === 'timesheet' ? 'starting_time' : 'start_time';
            $endColumn = $tableName === 'timesheet' ? 'ending_time' : 'end_time';
            
            // Check if user already has a running timer for this task
            $existingTimer = \DB::table($tableName)
                ->where('task_id', $id)
                ->where('user_id', $user->id)
                ->whereNull($endColumn)
                ->first();
                
            if ($existingTimer) {
                return response()->json([
                    'error' => true, 
                    'message' => 'Timer already running',
                    'timer_id' => $existingTimer->id
                ]);
            }
            
            // Create new timesheet entry
            $data = [
                'task_id' => $id,
                'user_id' => $user->id,
                $startColumn => now(),
            ];
            
            // Add timestamps if needed
            if ($tableName === 'timesheets') {
                $data['created_at'] = now();
                $data['updated_at'] = now();
            } else {
                $data['created'] = now();
            }
            
            if ($task->project_id) {
                $data['project_id'] = $task->project_id;
            }
            
            $timerId = \DB::table($tableName)->insertGetId($data);
            
            return response()->json([
                'error' => false,
                'message' => 'Timer started',
                'timer_id' => $timerId,
                'start_time' => now()->toIso8601String()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error starting timer: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => true, 
                'message' => 'Error starting timer: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Stop timer for a task
     */
    public function stopTimer($id)
    {
        try {
            $user = auth()->user();
            
            // Check which table exists
            $tableName = \Schema::hasTable('timesheet') ? 'timesheet' : 'timesheets';
            $startColumn = $tableName === 'timesheet' ? 'starting_time' : 'start_time';
            $endColumn = $tableName === 'timesheet' ? 'ending_time' : 'end_time';
            
            // Find running timer for this user and task
            $timer = \DB::table($tableName)
                ->where('task_id', $id)
                ->where('user_id', $user->id)
                ->whereNull($endColumn)
                ->first();
                
            if (!$timer) {
                return response()->json(['error' => true, 'message' => 'No running timer found'], 404);
            }
            
            $endTime = now();
            $startTime = \Carbon\Carbon::parse($timer->{$startColumn});
            $totalHours = $startTime->diffInMinutes($endTime) / 60;
            
            // Prepare update data
            $updateData = [
                $endColumn => $endTime,
                'total_hours' => round($totalHours, 2),
            ];
            
            if ($tableName === 'timesheets') {
                $updateData['updated_at'] = now();
            }
            
            // Update timesheet with end time and total hours
            \DB::table($tableName)
                ->where('id', $timer->id)
                ->update($updateData);
            
            return response()->json([
                'error' => false,
                'message' => 'Timer stopped',
                'total_hours' => round($totalHours, 2)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error stopping timer: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Error stopping timer'], 500);
        }
    }
    
    /**
     * Get timer status for a task
     */
    public function timerStatus($id)
    {
        try {
            $user = auth()->user();
            
            // Check which table exists
            $tableName = \Schema::hasTable('timesheet') ? 'timesheet' : 'timesheets';
            $startColumn = $tableName === 'timesheet' ? 'starting_time' : 'start_time';
            $endColumn = $tableName === 'timesheet' ? 'ending_time' : 'end_time';
            
            $timer = \DB::table($tableName)
                ->where('task_id', $id)
                ->where('user_id', $user->id)
                ->whereNull($endColumn)
                ->first();
                
            if ($timer) {
                $startTime = \Carbon\Carbon::parse($timer->{$startColumn});
                $elapsed = $startTime->diffInSeconds(now());
                
                return response()->json([
                    'error' => false,
                    'running' => true,
                    'timer_id' => $timer->id,
                    'start_time' => $timer->{$startColumn},
                    'elapsed_seconds' => $elapsed
                ]);
            }
            
            return response()->json([
                'error' => false,
                'running' => false
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getting timer status: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Error getting timer status'], 500);
        }
    }
    
    /**
     * Add comment to task
     */
    public function addComment(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $task = Task::findOrFail($id);
            
            // Validate
            $request->validate([
                'message' => 'required|string',
                'attachment' => 'nullable|file|max:10240' // 10MB max
            ]);
            
            // Check if task_comments table exists, create if not
            if (!\Schema::hasTable('task_comments')) {
                \Schema::create('task_comments', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('task_id');
                    $table->unsignedBigInteger('user_id');
                    $table->text('message');
                    $table->string('attachment')->nullable();
                    $table->timestamps();
                });
            }
            
            $attachmentPath = null;
            
            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $attachmentPath = $file->storeAs('task_attachments', $filename, 'public');
                $attachmentPath = 'storage/' . $attachmentPath;
            }
            
            // Create comment
            $commentId = \DB::table('task_comments')->insertGetId([
                'task_id' => $id,
                'user_id' => $user->id,
                'message' => $request->message,
                'attachment' => $attachmentPath,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return response()->json([
                'error' => false,
                'message' => 'Comment added successfully',
                'comment_id' => $commentId
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error adding comment: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => true, 
                'message' => 'Error adding comment: ' . $e->getMessage()
            ], 500);
        }
    }
}
