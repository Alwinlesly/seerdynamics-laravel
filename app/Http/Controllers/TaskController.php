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
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        $data['project_types'] = DB::table('project_type')->orderBy('id')->get();
        
        // Get projects for filter (based on user role)
        if ($user->inGroup(3) || $user->inGroup(4)) { // Customer admin/user - match CI get_projects() logic
            $clientIds = $user->getCustomerClientIds();
            $data['projects'] = Project::query()
                ->whereIn('client_id', $clientIds)
                ->where('is_visible', 0)
                ->get();
        } elseif (!$user->inGroup(1)) { // Non-admin/non-customer => assigned project_users only (CI behavior)
            $data['projects'] = Project::query()
                ->whereHas('users', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->get();
        } else { // Admin
            $data['projects'] = Project::all();
        }

        // For create-ticket project dropdown: exclude Closed/Finished projects.
        $inactiveProjectStatusIds = DB::table('project_status')
            ->select('id', 'title')
            ->get()
            ->filter(function ($row) {
                $title = strtolower(trim((string) $row->title));
                return in_array($title, ['finished', 'closed'], true);
            })
            ->pluck('id')
            ->all();

        $data['create_projects'] = collect($data['projects'] ?? [])
            ->filter(function ($project) use ($inactiveProjectStatusIds) {
                return !in_array((int) $project->status, array_map('intval', $inactiveProjectStatusIds), true);
            })
            ->values();
        
        // Get customers for filter
        if ($user->inGroup(1) || $user->inGroup(2)) {
            $data['customers'] = User::where('active', 1)
                ->whereHas('groups', function($q) {
                    $q->where('groups.id', 3); // Customer group ID
                })
                ->where('is_company', 1)
                ->get();
        } elseif ($user->inGroup(3)) {
            // Customer admin: only own/parent customer accounts
            $clientIds = $user->getCustomerClientIds();
            $data['customers'] = User::where('active', 1)
                ->whereIn('id', $clientIds)
                ->where('is_company', 1)
                ->get();
        } else {
            $data['customers'] = collect();
        }

        // Get services mapped by project (used to filter "Select service" by selected project in modal)
        $projectIds = collect($data['projects'] ?? [])->pluck('id')->filter()->values();
        $servicesQuery = DB::table('services')
            ->select('project', 'service')
            ->whereNotNull('service')
            ->where('service', '!=', '');

        if ($projectIds->isNotEmpty()) {
            $servicesQuery->whereIn('project', $projectIds);
        } else {
            // If no projects are visible for this user, keep service list empty.
            $servicesQuery->whereRaw('1 = 0');
        }

        $data['services'] = $servicesQuery
            ->distinct()
            ->orderBy('service')
            ->get();

        // Get Consultant Users
        $data['consultant_users'] = User::where('active', 1)
            ->whereHas('groups', function($q) {
                $q->where('groups.id', 2);
            })
            ->get();

        // Get Other Customer Users
        if ($user->inGroup(1) || $user->inGroup(3) || $user->inGroup(4)) {
            if ($user->inGroup(1)) {
                $data['other_cusers'] = collect(); // For Admin, populated dynamically based on selected project if needed or we can fetch all client users
            } else {
                $data['other_cusers'] = User::where('cuser_customer', $user->cuser_customer)
                   ->where('active', 1)
                   ->whereHas('groups', function($q) {
                       $q->where('groups.id', 4);
                   })->get();
            }
        } else {
            $data['other_cusers'] = collect();
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
            if ($user->inGroup(3)) { // Customer admin - all tasks for parent company projects
                $clientIds = $user->getCustomerClientIds();
                $query->whereHas('project', function($q) use ($clientIds) {
                    $q->whereIn('client_id', $clientIds)->where('is_visible', 0);
                });
            } elseif (!$user->inGroup(1)) { // Not admin - show assigned tasks
                $query->whereHas('users', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            
            // Search filter (all visible columns except Priority and Estimate)
            $search = trim((string) $request->input('search', ''));
            if ($search !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('tasks.title', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT('#', tasks.id) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(tasks.created, '%d-%b-%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereHas('project', function($projectQ) use ($search) {
                          $projectQ->where('project_id', 'like', "%{$search}%")
                              ->orWhere('title', 'like', "%{$search}%")
                              ->orWhereRaw("CONCAT(COALESCE(project_id, ''), ' ', COALESCE(title, '')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("CONCAT(COALESCE(title, ''), ' ', COALESCE(project_id, '')) LIKE ?", ["%{$search}%"])
                              ->orWhereHas('client', function($customerQ) use ($search) {
                                  $customerQ->whereRaw(
                                      "COALESCE(NULLIF(TRIM(company), ''), TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))) LIKE ?",
                                      ["%{$search}%"]
                                  );
                              });
                      })
                      ->orWhereHas('creator', function($creatorQ) use ($search) {
                          $creatorQ->whereRaw("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) LIKE ?", ["%{$search}%"])
                              ->orWhere('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('taskStatus', function($statusQ) use ($search) {
                          $statusQ->where('title', 'like', "%{$search}%");
                      });
                });
            }
            
            // Project filter
            if ($request->project) {
                $query->where('project_id', $request->project);
            }

            // Project type filter (legacy: projecttype / ptype)
            $projectType = $request->input('project_type');
            if (!empty($projectType)) {
                $query->whereHas('project', function($q) use ($projectType) {
                    $q->where('ptype', (int) $projectType);
                });
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

            // Build dynamic status summary counts using the current filters,
            // but without applying an explicit status filter.
            $statusCountsRows = (clone $query)
                ->leftJoin('task_status as ts', 'tasks.status', '=', 'ts.id')
                ->selectRaw("COALESCE(ts.title, 'Not Started') as status_title, COUNT(*) as total")
                ->groupBy('ts.title')
                ->get();

            $statusCounts = [];
            foreach ($statusCountsRows as $statusRow) {
                $key = strtolower(preg_replace('/[\s_-]+/', '', (string) $statusRow->status_title));
                $statusCounts[$key] = (int) $statusRow->total;
            }

            $preferredStatusOrder = [
                'todo',
                'inprogress',
                'undercustomerreview',
                'onhold',
                'completed',
                'closed',
            ];

            $allStatuses = TaskStatus::all()->pluck('title')->all();
            $normalizedDbTitles = [];
            foreach ($allStatuses as $statusTitle) {
                $normalizedDbTitles[strtolower(preg_replace('/[\s_-]+/', '', (string) $statusTitle))] = $statusTitle;
            }

            $orderedStatusTitles = [];
            foreach ($preferredStatusOrder as $normalizedStatus) {
                if (isset($normalizedDbTitles[$normalizedStatus])) {
                    $orderedStatusTitles[] = $normalizedDbTitles[$normalizedStatus];
                }
            }
            foreach ($allStatuses as $statusTitle) {
                if (!in_array($statusTitle, $orderedStatusTitles, true)) {
                    $orderedStatusTitles[] = $statusTitle;
                }
            }

            $statusSummary = [];
            foreach ($orderedStatusTitles as $statusTitle) {
                $normalizedStatus = strtolower(preg_replace('/[\s_-]+/', '', (string) $statusTitle));
                $statusSummary[] = [
                    'title' => $statusTitle,
                    'count' => $statusCounts[$normalizedStatus] ?? 0,
                ];
            }

            // Status filter - compare by ID not title
            if ($request->status) {
                $statusRecord = TaskStatus::where('title', $request->status)->first();
                if ($statusRecord) {
                    $query->where('status', $statusRecord->id);
                }
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
                        if ($customer) {
                            $company = trim((string) ($customer->company ?? ''));
                            $fullName = trim((string) (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')));
                            $customerName = $company !== '' ? $company : $fullName;
                        }
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

                $latestEstimate = TaskEstimate::where('task_id', $task->id)
                    ->orderByDesc('id')
                    ->first(['estimate_hours', 'estimate_status']);

                $estimateValue = ($latestEstimate && $latestEstimate->estimate_hours !== null)
                    ? $latestEstimate->estimate_hours
                    : ($task->estimate ?? 0);

                $isEstimateApproved = $latestEstimate
                    ? ((int) $latestEstimate->estimate_status === 1)
                    : false;
                
                $rows[] = [
                    'id' => $task->id,
                    'ticket_id' => $task->ticket_id ?? '#' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                    'title' => $task->title ?? '',
                    'project' => $projectName,
                    'customer' => $customerName,
                    'estimate' => $estimateValue,
                    'is_estimate_approved' => $isEstimateApproved,
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
                'rows' => $rows,
                'status_summary' => $statusSummary,
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
                'creator',
                'files'
            ])->findOrFail($id);
            
            // Try to load comments if table exists
            if (\Schema::hasTable('task_comments')) {
                try {
                    $task->load('comments.user', 'comments.files');
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
            
            // Check access (mirror CI condition for customer admin)
            if ($user->inGroup(3) && $task->project) {
                $clientIds = $user->getCustomerClientIds();
                if (!in_array($task->project->client_id, $clientIds) || (int) $task->project->is_visible !== 0) {
                    if (request()->ajax()) {
                        return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                    }
                    abort(403, 'Access Denied');
                }
            }

            // Mirror CI split: task assignees are separated into customer users and consultants/admins.
            $task->loadMissing('users.groups');
            $taskCustomerUsers = $task->users->filter(function ($assignedUser) {
                $groupIds = $assignedUser->groups->pluck('id');
                return $groupIds->contains(3) || $groupIds->contains(4);
            })->values();
            $taskConsultantUsers = $task->users->filter(function ($assignedUser) {
                $groupIds = $assignedUser->groups->pluck('id');
                return $groupIds->contains(1) || $groupIds->contains(2);
            })->values();

            $canSeeTime = !$user->inGroup(3);
            $currentEstimateHours = TaskEstimate::where('task_id', $task->id)
                ->orderByDesc('id')
                ->value('estimate_hours');
            if ($currentEstimateHours === null) {
                $currentEstimateHours = $task->estimate ?? 0;
            }

            // Fetch weekly timesheet entries booked against this task
            $weeklyTimesheetEntries = collect();
            try {
                $weeklyTimesheetEntries = DB::table('weekly_timesheet_project_task_details as wptd')
                    ->join('weekly_timesheet_project_task_hours as wpth', 'wpth.timesheet_project_task_id', '=', 'wptd.id')
                    ->join('weekly_timesheet as wt', 'wt.id', '=', 'wptd.timesheet_id')
                    ->join('users as u', 'u.id', '=', 'wt.user_id')
                    ->where('wptd.task_id', $task->id)
                    ->where('wpth.status', 1)
                    ->where('wptd.status', 1)
                    ->select(
                        'u.first_name',
                        'u.last_name',
                        'u.profile_picture',
                        'wpth.date',
                        'wpth.hour',
                        'wpth.released_hour',
                        'wpth.release_status'
                    )
                    ->orderBy('wpth.date', 'desc')
                    ->get();
            } catch (\Exception $e) {
                \Log::warning('Could not load weekly timesheet entries: ' . $e->getMessage());
            }

            // Return partial view for AJAX requests (modal)
            if (request()->ajax()) {
                $html = view('tasks.partials.detail-content', [
                    'task' => $task,
                    'weeklyTimesheetEntries' => $weeklyTimesheetEntries,
                    'taskCustomerUsers' => $taskCustomerUsers,
                    'taskConsultantUsers' => $taskConsultantUsers,
                    'currentEstimateHours' => $currentEstimateHours,
                ])->render();
                return response()->json([
                    'error' => false,
                    'html' => $html,
                    'can_see_time' => $canSeeTime,
                    'can_add_estimate' => ($user->inGroup(1) || $user->inGroup(2)),
                ]);
            }

            // Return full page view
            $data = [
                'page_title' => 'Task Details - ' . company_name(),
                'current_user' => $user,
                'task' => $task,
                'currentEstimateHours' => $currentEstimateHours,
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
            $task = Task::with(['taskStatus', 'users', 'taskPriority', 'issueType', 'files'])->findOrFail($id);
            
            // Check access
            if ($user->inGroup(3) && $task->project) {
                $clientIds = $user->getCustomerClientIds();
                if (!in_array($task->project->client_id, $clientIds)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
            }
            
            if (!$user->inGroup(1) && !$user->inGroup(3)) {
                if (!$task->users->contains('id', $user->id)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
            }

            // Full edit is not available for customer admin/user in the new flow.
            // They may still access this endpoint only for close-mode statuses.
            if ($user->inGroup(3) || $user->inGroup(4)) {
                $statusTitle = strtolower(preg_replace('/[\s_-]+/', '', (string) ($task->taskStatus->title ?? '')));
                if (!in_array($statusTitle, ['completed', 'onhold', 'closed'], true)) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Full ticket edit is not available for customer users'
                    ], 403);
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
                'attachments' => $task->files->map(function ($file) {
                    return [
                        'name' => $file->file_name,
                        'path' => 'assets/uploads/tasks/' . $file->file_name,
                    ];
                })->values()->toArray(),
                'additional_mail' => $task->additional_mail,
                'users' => $task->users->pluck('id')->toArray(),
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
            'issue_type_id' => 'nullable|exists:issue_types,issue_type_id',
            'priority_id' => 'nullable|exists:priorities,id',
        ]);
        
        try {
            // For customer admin, force status to To Do; others use submitted status
            if (auth()->user()->inGroup(3)) {
                $todoStatus = TaskStatus::all()->first(function ($statusItem) {
                    return strtolower(preg_replace('/[\s_-]+/', '', $statusItem->title)) === 'todo';
                });
                $validated['status'] = $todoStatus ? $todoStatus->id : 1;
            } else {
                $status = TaskStatus::where('title', $request->status)->first();
                $validated['status'] = $status ? $status->id : 1;
            }
            
            // Get priority ID from request or use default
            $validated['priority'] = $request->priority_id ?? 1;
            
            // Get issue type
            $validated['issue_type'] = $request->issue_type_id ?? null;
            
            $validated['service'] = $request->service;
            $validated['additional_mail'] = $request->additional_mail;
            $validated['created_by'] = auth()->id();
            $validated['created'] = now();
            $validated['due_date'] = $request->issue_date;
            $validated['attachment'] = '';
            
            // Ticket ID is dynamically generated by incrementing padding so we do not store it
            
            $task = Task::create($validated);

            // Handle single/multiple file uploads
            if ($request->hasFile('attachment')) {
                $files = $request->file('attachment');
                if (!is_array($files)) {
                    $files = [$files];
                }

                $primaryAttachment = null;
                foreach ($files as $file) {
                    if (!$file) {
                        continue;
                    }

                    $fileSize = $file->getSize();
                    $fileType = strtolower($file->getClientOriginalExtension() ?: 'file');
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('assets/uploads/tasks'), $filename);

                    MediaFile::create([
                        'type' => 'task',
                        'type_id' => $task->id,
                        'user_id' => auth()->id(),
                        'file_name' => $filename,
                        'file_type' => $fileType,
                        'file_size' => $fileSize,
                        'created' => now(),
                    ]);

                    if ($primaryAttachment === null) {
                        $primaryAttachment = 'assets/uploads/tasks/' . $filename;
                    }
                }

                // Keep legacy single attachment column populated with first file
                if ($primaryAttachment) {
                    $task->attachment = $primaryAttachment;
                    $task->save();
                }
            }
            
            // Mirror legacy CI assignment behavior:
            // - if users are selected, assign them
            // - if creator is customer user (group 4), also assign creator even when users are selected
            // - if no users are selected, assign creator
            $assignedUserIds = [];
            if ($request->has('users') && is_array($request->users)) {
                $assignedUserIds = collect($request->users)
                    ->filter(fn ($id) => is_numeric($id))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            }

            $currentUserId = (int) auth()->id();
            if (auth()->user()->inGroup(4)) {
                if (!in_array($currentUserId, $assignedUserIds, true)) {
                    $assignedUserIds[] = $currentUserId;
                }
            } elseif (empty($assignedUserIds)) {
                $assignedUserIds[] = $currentUserId;
            }

            $task->users()->sync($assignedUserIds);
            
            // Send email notifications (replicating CI's mail_me logic)
            try {
                $data = [];
                EmailService::sendTicketEmail($task->id, 'NTKT', $data);
                
                // Additional status-based emails
                $statusId = $validated['status'];
                if ($statusId == 2) {
                    EmailService::sendTicketEmail($task->id, 'ASGNCONSLT', $data);
                } elseif ($statusId == 4) {
                    EmailService::sendTicketEmail($task->id, 'TKTCOMPL', $data);
                } elseif ($statusId == 5) {
                    EmailService::sendTicketEmail($task->id, 'TKTCLOSE', $data);
                }
            } catch (\Exception $emailEx) {
                \Log::error('Email notification failed for new task: ' . $emailEx->getMessage());
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

        $user = auth()->user();
        $isCustomerCloser = $user->inGroup(3) || $user->inGroup(4);
        $isConsultant = $user->inGroup(2);
        $canGeneralEdit = $user->inGroup(1) || permissions('task_edit');

        // General edit is for admin/permitted internal users.
        // Customer admin/user are allowed only close action via this endpoint.
        if (!$canGeneralEdit && !$isCustomerCloser) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }

        try {
            $requestedStatusTitle = trim((string) $request->input('status', ''));
            $requestedStatus = $requestedStatusTitle !== ''
                ? TaskStatus::where('title', $requestedStatusTitle)->first()
                : null;

            // Consultant flow: status-only update (no full field edit).
            if ($isConsultant) {
                if (!$this->canAccessTask($user, $task)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }

                if (!$requestedStatus) {
                    return response()->json(['error' => true, 'message' => 'Status is required'], 422);
                }

                $oldStatus = (int) $task->status;
                $newStatus = (int) $requestedStatus->id;

                $allStatuses = TaskStatus::all();
                $statusTitleById = [];
                foreach ($allStatuses as $statusRow) {
                    $statusTitleById[(int) $statusRow->id] = strtolower(preg_replace('/[\s_-]+/', '', (string) $statusRow->title));
                }
                $currentNormalized = $statusTitleById[$oldStatus] ?? '';
                $requestedNormalized = strtolower(preg_replace('/[\s_-]+/', '', (string) $requestedStatus->title));

                if (in_array($currentNormalized, ['closed', 'completed'], true)) {
                    return response()->json([
                        'error' => true,
                        'message' => 'This Ticket has been Closed/Completed'
                    ], 422);
                }

                $allowedTransitions = [
                    'inprogress' => ['undercustomerreview', 'onhold', 'completed'],
                    'undercustomerreview' => ['inprogress', 'onhold', 'completed'],
                    'onhold' => ['inprogress', 'undercustomerreview', 'completed'],
                ];

                if (isset($allowedTransitions[$currentNormalized]) && !in_array($requestedNormalized, $allowedTransitions[$currentNormalized], true)) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid status transition for consultant'
                    ], 422);
                }

                $task->status = $newStatus;
                if ($requestedNormalized === 'completed' && $currentNormalized !== 'completed') {
                    $task->completed_date = now();
                }
                if ($requestedNormalized === 'closed') {
                    $task->closed_date = now();
                }
                $task->save();

                try {
                    $data = [];
                    if ($requestedNormalized === 'completed' && $currentNormalized !== 'completed') {
                        EmailService::sendTicketEmail($task->id, 'TKTCOMPL', $data);
                    } elseif ($requestedNormalized === 'closed' && $currentNormalized !== 'closed') {
                        EmailService::sendTicketEmail($task->id, 'TKTCLOSE', $data);
                    }
                } catch (\Exception $emailEx) {
                    \Log::error('Email notification failed for consultant status update: ' . $emailEx->getMessage());
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Task status updated successfully'
                ]);
            }

            // Customer admin/user: allow only "Completed -> Closed" flow.
            if ($isCustomerCloser) {
                if (!$this->canAccessTask($user, $task)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }

                $completedStatus = TaskStatus::where('title', 'Completed')->first();
                $onHoldStatus = TaskStatus::where('title', 'On Hold')->first();
                $closedStatus = TaskStatus::where('title', 'Closed')->first();

                if (!$closedStatus) {
                    return response()->json(['error' => true, 'message' => 'Closed status is not configured'], 422);
                }

                $requestedStatusId = $requestedStatus ? (int) $requestedStatus->id : 0;
                $onHoldStatusId = $onHoldStatus ? (int) $onHoldStatus->id : 0;
                $closedStatusId = (int) $closedStatus->id;

                if (!in_array($requestedStatusId, array_filter([$closedStatusId, $onHoldStatusId]), true)) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Customer users can set status only to Closed or On Hold'
                    ], 422);
                }

                $allowedStatuses = [];
                if ($completedStatus) {
                    $allowedStatuses[] = (int) $completedStatus->id;
                }
                if ($onHoldStatus) {
                    $allowedStatuses[] = (int) $onHoldStatus->id;
                }

                if (empty($allowedStatuses) || !in_array((int) $task->status, $allowedStatuses, true)) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Only completed or on-hold tickets can be closed'
                    ], 422);
                }

                $task->status = $requestedStatusId;
                if ($requestedStatusId === $closedStatusId) {
                    $task->closed_date = now();
                }
                $task->save();

                // Existing project behavior: in close/update popup customer admin can add users
                // without dropping already assigned users.
                if ($request->filled('users')) {
                    $incomingUsers = array_values(array_unique(array_map('intval', (array) $request->input('users', []))));
                    if (!empty($incomingUsers)) {
                        $existingUsers = $task->users()->pluck('users.id')->map(function ($id) {
                            return (int) $id;
                        })->toArray();
                        $mergedUsers = array_values(array_unique(array_merge($existingUsers, $incomingUsers)));
                        $task->users()->sync($mergedUsers);
                    }
                }

                try {
                    if ($requestedStatusId === $closedStatusId) {
                        EmailService::sendTicketEmail($task->id, 'TKTCLOSE', []);
                    }
                } catch (\Exception $emailEx) {
                    \Log::error('Email notification failed for customer close: ' . $emailEx->getMessage());
                }

                return response()->json([
                    'error' => false,
                    'message' => $requestedStatusId === $closedStatusId ? 'Ticket closed successfully' : 'Ticket moved to On Hold successfully'
                ]);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'project_id' => 'required|exists:projects,id',
                'issue_type_id' => 'nullable|exists:issue_types,issue_type_id',
                'priority_id' => 'nullable|exists:priorities,id',
            ]);

            // Get status ID from title
            $validated['status'] = $requestedStatus ? $requestedStatus->id : $task->status;
            
            // Update priority
            $validated['priority'] = $request->priority_id ?? $task->priority;
            
            // Update issue type
            $validated['issue_type'] = $request->issue_type_id ?? $task->issue_type;
            
            $validated['service'] = $request->service;
            $validated['additional_mail'] = $request->additional_mail;
            $validated['due_date'] = $request->issue_date;
            
            // Handle file upload
            if ($request->hasFile('attachment')) {
                // Delete old legacy file
                if ($task->attachment && file_exists(public_path($task->attachment))) {
                    unlink(public_path($task->attachment));
                }

                // Delete old media_files attachments
                $existingTaskFiles = MediaFile::where('type', 'task')->where('type_id', $task->id)->get();
                foreach ($existingTaskFiles as $existingFile) {
                    $existingPath = public_path('assets/uploads/tasks/' . $existingFile->file_name);
                    if (file_exists($existingPath)) {
                        unlink($existingPath);
                    }
                    $existingFile->delete();
                }

                $files = $request->file('attachment');
                if (!is_array($files)) {
                    $files = [$files];
                }

                $primaryAttachment = null;
                foreach ($files as $file) {
                    if (!$file) {
                        continue;
                    }

                    $fileSize = $file->getSize();
                    $fileType = strtolower($file->getClientOriginalExtension() ?: 'file');
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('assets/uploads/tasks'), $filename);

                    MediaFile::create([
                        'type' => 'task',
                        'type_id' => $task->id,
                        'user_id' => auth()->id(),
                        'file_name' => $filename,
                        'file_type' => $fileType,
                        'file_size' => $fileSize,
                        'created' => now(),
                    ]);

                    if ($primaryAttachment === null) {
                        $primaryAttachment = 'assets/uploads/tasks/' . $filename;
                    }
                }

                if ($primaryAttachment) {
                    $validated['attachment'] = $primaryAttachment;
                }
            }
            
            // Save old status before update
            $oldStatus = $task->status;
            
            $task->update($validated);
            
            // Update assignees only when users[] is explicitly submitted.
            // This preserves existing task_users for consultant edits where
            // assign-users controls are not present in the modal.
            if ($request->has('users')) {
                $users = $request->users;
                if (is_array($users)) {
                    $task->users()->sync($users);
                } else {
                    $task->users()->sync([]);
                }
            }
            
            // Send email notifications based on status change (replicating CI logic)
            try {
                $newStatus = $validated['status'];
                $data = [];
                
                // If old status was Created (1) and consultant assigned, send ASGNCONSLT
                if ($oldStatus == 1 && $newStatus != 1) {
                    EmailService::sendTicketEmail($task->id, 'ASGNCONSLT', $data);
                }
                
                // Status changed to Completed (4)
                if ($newStatus == 4 && $oldStatus != 4) {
                    $task->update(['completed_date' => now()]);
                    EmailService::sendTicketEmail($task->id, 'TKTCOMPL', $data);
                }
                // Status changed to Closed (5)
                elseif ($newStatus == 5 && $oldStatus != 5) {
                    $task->update(['closed_date' => now()]);
                    EmailService::sendTicketEmail($task->id, 'TKTCLOSE', $data);
                }
            } catch (\Exception $emailEx) {
                \Log::error('Email notification failed for task update: ' . $emailEx->getMessage());
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
            
            // Delete legacy attachment
            if ($task->attachment && file_exists(public_path($task->attachment))) {
                unlink(public_path($task->attachment));
            }

            // Delete multi-attachments (media_files)
            $taskFiles = MediaFile::where('type', 'task')->where('type_id', $task->id)->get();
            foreach ($taskFiles as $taskFile) {
                $filePath = public_path('assets/uploads/tasks/' . $taskFile->file_name);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $taskFile->delete();
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
                $clientIds = $user->getCustomerClientIds();
                $query->whereHas('project', function($q) use ($clientIds) {
                    $q->whereIn('client_id', $clientIds)->where('is_visible', 0);
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

            // Match task list behavior: use latest estimate_hours from task_estimate, fallback to tasks.estimate.
            $latestEstimateByTask = collect();
            $taskIds = $tasks->pluck('id')->filter()->values();
            if ($taskIds->isNotEmpty()) {
                $latestEstimateByTask = TaskEstimate::query()
                    ->whereIn('task_id', $taskIds)
                    ->orderByDesc('id')
                    ->get(['task_id', 'estimate_hours'])
                    ->unique('task_id')
                    ->keyBy('task_id');
            }
            
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
                $latestEstimate = $latestEstimateByTask->get($task->id);
                $estimateValue = $latestEstimate && $latestEstimate->estimate_hours !== null
                    ? $latestEstimate->estimate_hours
                    : ($task->estimate ?? 0);

                fputcsv($handle, [
                    $task->ticket_id ?? '#' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                    $task->title,
                    $task->description,
                    $task->project ? $task->project->project_id . ' ' . $task->project->title : '',
                    $task->project && $task->project->client ? 
                        ($task->project->client->company ?? $task->project->client->first_name) : '',
                    $task->taskStatus->title ?? 'Not Started',
                    $task->taskPriority->title ?? '',
                    $estimateValue,
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
     * Get estimates for a task (mirror CI get_estimate behavior).
     */
    public function getEstimates($id)
    {
        try {
            $user = auth()->user();
            $task = Task::with('project')->findOrFail($id);

            if (!$this->canAccessTask($user, $task)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $estimates = TaskEstimate::with(['user', 'approver'])
                ->where('task_id', $id)
                ->orderByDesc('created')
                ->get()
                ->map(function ($estimate) use ($user) {
                    $firstName = $estimate->user->first_name ?? '';
                    $lastName = $estimate->user->last_name ?? '';
                    $shortName = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

                    $canApprove = ($user->inGroup(3) || $user->inGroup(4)) && (int) $estimate->estimate_status === 0;
                    $canEdit = ($user->inGroup(1) || $user->inGroup(2)) && (int) $estimate->estimate_status === 0;

                    $approvedBy = '';
                    if ((int) $estimate->estimate_status === 1 && (int) $estimate->estimate_approvedby > 0 && $estimate->approver) {
                        $approvedBy = 'Approved By: ' . trim(($estimate->approver->first_name ?? '') . ' ' . ($estimate->approver->last_name ?? ''));
                    }

                    return [
                        'id' => $estimate->id,
                        'task_id' => $estimate->task_id,
                        'user_id' => $estimate->user_id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'profile' => $estimate->user->profile ?? null,
                        'short_name' => $shortName,
                        'estimate_func' => $estimate->estimate_func,
                        'estimate_tech' => $estimate->estimate_tech,
                        'estimate_days' => $estimate->estimate_days,
                        'estimate_hours' => $estimate->estimate_hours,
                        'estimate_status' => (int) $estimate->estimate_status,
                        'estimate_approvedby' => $estimate->estimate_approvedby,
                        'estimate_approvedon' => $estimate->estimate_approvedon ? date('d-M-Y', strtotime($estimate->estimate_approvedon)) : '',
                        'created' => $estimate->created ? date('d-M-Y', strtotime($estimate->created)) : '',
                        'can_delete' => $user->inGroup(1),
                        'can_edit' => $canEdit,
                        'can_approve' => $canApprove,
                        'is_customer' => ($user->inGroup(3) || $user->inGroup(4)),
                        'approved_by' => $approvedBy,
                    ];
                })
                ->values();

            return response()->json([
                'error' => false,
                'data' => $estimates,
                'message' => 'Successful',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching estimates: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error loading estimates',
            ], 500);
        }
    }

    /**
     * Create estimate (allowed for admin/consultant only; not customer admin).
     */
    public function storeEstimate(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $task = Task::with('project')->findOrFail($id);

            if (!$this->canAccessTask($user, $task)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            if (!$user->inGroup(1) && !$user->inGroup(2)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $validated = $request->validate([
                'estimate_func' => 'required|numeric|min:0.01',
                'estimate_tech' => 'nullable|numeric|min:0',
                'estimate_days' => 'nullable|numeric|min:0',
                'estimate_hours' => 'nullable|numeric|min:0',
            ]);

            $func = (float) $validated['estimate_func'];
            $tech = (float) ($validated['estimate_tech'] ?? 0);
            $hours = array_key_exists('estimate_hours', $validated) ? (float) $validated['estimate_hours'] : ($func + $tech);
            $days = array_key_exists('estimate_days', $validated) ? (float) $validated['estimate_days'] : ($hours / 8);

            TaskEstimate::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'estimate_amount' => $hours,
                'estimate_func' => $func,
                'estimate_tech' => $tech,
                'estimate_days' => $days,
                'estimate_hours' => $hours,
                'estimate_status' => 0,
                'created' => now(),
            ]);

            // Keep tasks.estimate in sync when legacy column exists.
            if (\Schema::hasColumn('tasks', 'estimate')) {
                $task->estimate = $hours;
                $task->save();
            }

            try {
                EmailService::sendTicketEmail($task->id, 'ESTIMATE', [
                    'estimate_func' => $func,
                    'estimate_tech' => $tech,
                    'estimate_days' => $days,
                    'estimate_hours' => $hours,
                ]);
            } catch (\Exception $emailEx) {
                \Log::error('Estimate email failed: ' . $emailEx->getMessage());
            }

            return response()->json([
                'error' => false,
                'message' => 'Estimate created successfully.',
            ]);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Validation failed';
            return response()->json([
                'error' => true,
                'message' => $message,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating estimate: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error creating estimate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve estimate (allowed for customer admin/customer user only).
     */
    public function approveEstimate($estimateId)
    {
        try {
            $user = auth()->user();

            if (!$user->inGroup(3) && !$user->inGroup(4)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $estimate = TaskEstimate::with('task.project')->findOrFail($estimateId);
            if (!$estimate->task || !$this->canAccessTask($user, $estimate->task)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $estimate->update([
                'estimate_status' => 1,
                'estimate_approvedon' => now(),
                'estimate_approvedby' => $user->id,
            ]);

            try {
                EmailService::sendTicketEmail($estimate->task_id, 'ESTAPPRV', [
                    'estimate_id' => $estimate->id,
                ]);
            } catch (\Exception $emailEx) {
                \Log::error('Estimate approval email failed: ' . $emailEx->getMessage());
            }

            return response()->json([
                'error' => false,
                'message' => 'Estimate approved successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error approving estimate: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error approving estimate',
            ], 500);
        }
    }

    /**
     * Close ticket from tasks list (customer admin flow):
     * allowed only when current status is Completed.
     */
    public function close($id)
    {
        try {
            $user = auth()->user();
            $task = Task::with('project')->findOrFail($id);

            // Existing project behavior: customer admin can close completed tickets.
            if (!$user->inGroup(3) && !$user->inGroup(4)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            if (!$this->canAccessTask($user, $task)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $completedStatus = TaskStatus::where('title', 'Completed')->first();
            $onHoldStatus = TaskStatus::where('title', 'On Hold')->first();
            $closedStatus = TaskStatus::where('title', 'Closed')->first();

            if (!$closedStatus) {
                return response()->json(['error' => true, 'message' => 'Closed status is not configured'], 422);
            }

            $allowedStatuses = [];
            if ($completedStatus) {
                $allowedStatuses[] = (int) $completedStatus->id;
            }
            if ($onHoldStatus) {
                $allowedStatuses[] = (int) $onHoldStatus->id;
            }

            if (empty($allowedStatuses) || !in_array((int) $task->status, $allowedStatuses, true)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Only completed or on-hold tickets can be closed'
                ], 422);
            }

            $task->status = $closedStatus->id;
            $task->closed_date = now();
            $task->save();

            try {
                EmailService::sendTicketEmail($task->id, 'TKTCLOSE', []);
            } catch (\Exception $emailEx) {
                \Log::error('Ticket close email failed: ' . $emailEx->getMessage());
            }

            return response()->json([
                'error' => false,
                'message' => 'Ticket closed successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error closing ticket: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error closing ticket'
            ], 500);
        }
    }

    private function canAccessTask($user, Task $task): bool
    {
        if ($user->inGroup(1)) {
            return true;
        }

        if ($user->inGroup(3)) {
            if (!$task->project) {
                return false;
            }
            $clientIds = $user->getCustomerClientIds();
            return in_array($task->project->client_id, $clientIds) && (int) $task->project->is_visible === 0;
        }

        return $task->users()->where('user_id', $user->id)->exists();
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
                'attachment' => 'nullable|array',
                'attachment.*' => 'file|max:10240' // 10MB each
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
            $uploadedFiles = [];
            
            // Handle file upload
            if ($request->hasFile('attachment')) {
                $files = $request->file('attachment');
                if (!is_array($files)) {
                    $files = [$files];
                }

                $uploadedFiles = [];
                foreach ($files as $file) {
                    if (!$file) {
                        continue;
                    }
                    $fileSize = $file->getSize();
                    $destinationPath = public_path('assets/uploads/task_comments');
                    if (!is_dir($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->move($destinationPath, $filename);
                    $uploadedFiles[] = [
                        'name' => $filename,
                        'size' => $fileSize,
                        'type' => strtolower($file->getClientOriginalExtension() ?: 'file'),
                        'path' => 'assets/uploads/task_comments/' . $filename,
                    ];
                }

                if (!empty($uploadedFiles)) {
                    // Keep legacy comment attachment column with first file
                    $attachmentPath = $uploadedFiles[0]['path'];
                }
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

            // Save all comment files in media_files
            if (!empty($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    MediaFile::create([
                        'type' => 'task_comment',
                        'type_id' => $commentId,
                        'user_id' => $user->id,
                        'file_name' => $uploadedFile['name'],
                        'file_type' => $uploadedFile['type'],
                        'file_size' => $uploadedFile['size'],
                        'created' => now(),
                    ]);
                }
            }
            
            // Send email notification for new message (replicating CI's NMSG action)
            try {
                $emailData = [
                    'message' => $request->message,
                    'IsNone' => $attachmentPath ? '' : 'none',
                    'attachmment' => $attachmentPath ? basename($attachmentPath) : '',
                ];
                EmailService::sendTicketEmail($id, 'NMSG', $emailData);
            } catch (\Exception $emailEx) {
                \Log::error('Email notification failed for comment: ' . $emailEx->getMessage());
            }
            
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
