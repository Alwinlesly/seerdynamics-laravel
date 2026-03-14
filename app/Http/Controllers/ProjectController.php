<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\MediaFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Display projects listing page
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $data = [
            'page_title' => 'Projects - ' . company_name(),
            'current_user' => $user,
        ];
        
        // Get filter options
        $data['customers'] = User::whereHas('groups', function($q) {
            $q->where('groups.id', 3);
        })->get();
        
        $data['consultants'] = User::whereHas('groups', function($q) {
            $q->where('groups.id', 2);
        })->get();
        
        // Get project statuses for filter
        $data['project_statuses'] = \App\Models\ProjectStatus::all();
        $data['project_types'] = DB::table('project_type')->orderBy('id')->get();
        
        // Get projects for filter (based on user role)
        if ($user->inGroup(3)) { // Customer - show projects for this user and parent company
            $clientIds = $user->getCustomerClientIds();
            $data['projects'] = Project::whereIn('client_id', $clientIds)->where('is_visible', 0)->get();
        } elseif (!$user->inGroup(1)) { // Not admin
            $data['projects'] = Project::whereHas('users', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
        } else { // Admin
            $data['projects'] = Project::all();
        }
        
        return view('projects.index', $data);
    }
    
    /**
     * Get projects list (AJAX)
     */
    public function getProjects(Request $request)
    {
        try {
            $user = auth()->user();
            $query = Project::query();
            
            // Apply role-based filtering
            if ($user->inGroup(3)) { // Customer - show projects for this user and parent company
                $clientIds = $user->getCustomerClientIds();
                $query->whereIn('client_id', $clientIds)->where('is_visible', 0);
            } elseif (!$user->inGroup(1)) { // Not admin
                $query->whereHas('users', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            
            // Search filter
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')
                      ->orWhere('project_id', 'like', '%'.$request->search.'%')
                      ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            }
            
            // Project filter
            if ($request->project) {
                $query->where('id', $request->project);
            }
            
            // Status filter (by title from project_status table)
            if ($request->status) {
                $query->where('status', (int) $request->status);
            }
            
            // Customer filter
            if ($request->customer) {
                $query->where('client_id', $request->customer);
            }
            
            // Consultant filter
            if ($request->consultant) {
                $query->whereHas('users', function($q) use ($request) {
                    $q->where('user_id', $request->consultant);
                });
            }
            
            // Sorting (with validation)
            $allowedSorts = ['id', 'created', 'title', 'project_id'];
            $sort = $request->sort ?? 'id';
            
            // Debug log
            \Log::info('Sort requested: ' . ($request->sort ?? 'null'));
            
            // Fallback: convert created_at to created (for browser cache compatibility)
            if ($sort === 'created_at') {
                \Log::info('Converting created_at to created');
                $sort = 'created';
            }
            
            if (!in_array($sort, $allowedSorts)) {
                $sort = 'id';
            }
            
            \Log::info('Sort being used: ' . $sort);
            $query->orderBy($sort, 'desc');
            
            $limit = $request->limit ?? 20;
            $offset = $request->offset ?? 0;
            
            $total = $query->count();
            $projects = $query->with(['customer', 'projectStatus', 'tasks'])->skip($offset)->take($limit)->get();
            
            // Format data
            $rows = $projects->map(function($project) {
                return [
                    'id' => $project->id,
                    'project_id' => $project->project_id ?? 'N/A',
                    'title' => $project->title ?? 'Untitled',
                    'customer' => $project->customer->company ?? $project->customer->first_name ?? 'N/A',
                    'tickets' => $project->completed_tasks . '/' . $project->tasks->count(),
                    'from' => $project->starting_date ? $project->starting_date->format('d-M-Y') : 'N/A',
                    'to' => $project->ending_date ? $project->ending_date->format('d-M-Y') : 'N/A',
                    'total_hours' => $project->hours ?? 0,
                    'status' => $project->projectStatus->title ?? 'Open',
                ];
            });
            
            \Log::info('Projects loaded', ['total' => $total, 'rows_count' => $rows->count()]);
            
            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading projects: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'total' => 0,
                'rows' => [],
            ], 500);
        }
    }
    
    /**
     * Show project details
     */
    public function show($id)
    {
        $user = auth()->user();
        $project = Project::with(['customer', 'tasks', 'users', 'files'])->findOrFail($id);
        
        // Check access
        if ($user->inGroup(3)) {
            $clientIds = $user->getCustomerClientIds();
            if (!in_array($project->client_id, $clientIds)) {
                abort(403, 'Access Denied');
            }
        } elseif (!$user->inGroup(1)) {
            if (!$project->users->contains('id', $user->id)) {
                abort(403, 'Access Denied');
            }
        }
        
        $data = [
            'page_title' => 'Project Details - ' . company_name(),
            'current_user' => $user,
            'project' => $project,
        ];
        $data['services_offered'] = DB::table('services')->where('project', $project->id)->pluck('service')->implode(', ');
        $data['project_type_title'] = DB::table('project_type')->where('id', $project->ptype)->value('title');
        
        // Calculate stats
        $data['total_tickets'] = $project->tasks->count();
        $data['completed_tickets'] = $project->completed_tasks;
        $data['pending_tickets'] = $data['total_tickets'] - $data['completed_tickets'];
        // Calculate days left
        if ($project->ending_date) {
            $data['days_left'] = now()->diffInDays($project->ending_date, false);
        } else {
            $data['days_left'] = 0;
        }
        
        // Data needed for the edit modal
        $data['customers'] = User::whereHas('groups', function($q) {
            $q->where('groups.id', 3);
        })->get();
        $data['project_statuses'] = \App\Models\ProjectStatus::all();
        $data['project_types'] = DB::table('project_type')->orderBy('id')->get();
        $data['consultants'] = User::whereHas('groups', function($q) {
            $q->where('groups.id', 2);
        })->get();
        
        return view('projects.show', $data);
    }
    
    /**
     * Get project data for editing
     */
    public function edit($id)
    {
        try {
            $user = auth()->user();
            $project = Project::with(['projectStatus', 'users'])->findOrFail($id);
            
            // Check access
            if ($user->inGroup(3)) {
                $clientIds = $user->getCustomerClientIds();
                if (!in_array($project->client_id, $clientIds)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
            } elseif (!$user->inGroup(1)) {
                if (!$project->users->contains('id', $user->id)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
            }
            
            // Format project data for the form
            $projectData = [
                'id' => $project->id,
                'project_id' => $project->project_id,
                'title' => $project->title,
                'description' => $project->description,
                'services' => DB::table('services')->where('project', $project->id)->pluck('service')->implode(', '),
                'starting_date' => $project->starting_date ? date('Y-m-d', strtotime($project->starting_date)) : '',
                'ending_date' => $project->ending_date ? date('Y-m-d', strtotime($project->ending_date)) : '',
                'actual_starting_date' => $project->actual_starting_date ? date('Y-m-d', strtotime($project->actual_starting_date)) : '',
                'actual_ending_date' => $project->actual_ending_date ? date('Y-m-d', strtotime($project->actual_ending_date)) : '',
                'budget' => $project->budget,
                'project_currency' => $project->project_currency,
                'hours' => $project->hours,
                'status' => $project->status,
                'status_title' => $project->projectStatus->title ?? '',
                'ptype' => $project->ptype,
                'client_id' => $project->client_id,
                'is_default' => $project->is_default,
                'is_visible' => $project->is_visible,
                'manager_id' => $project->manager_id,
                'contract_copy' => $project->contract_copy,
                'assigned_users' => $project->users->pluck('id')->toArray(),
            ];
            
            return response()->json([
                'error' => false,
                'project' => $projectData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading project for edit: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error loading project data'
            ], 500);
        }
    }
    
    /**
     * Store new project
     */
    public function store(Request $request)
    {
        if (!auth()->user()->inGroup(1) && !permissions('project_create')) {
            if (!$request->ajax() && !$request->wantsJson()) {
                return redirect()->route('projects.index')->with('error', 'Access Denied');
            }
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'project_id' => 'required|string|max:30|unique:projects,project_id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'starting_date' => 'required|date',
            'ending_date' => 'required|date|after_or_equal:starting_date',
            'actual_starting_date' => 'nullable|date',
            'actual_ending_date' => 'nullable|date',
            'status' => 'required|integer|exists:project_status,id',
            'client_id' => 'required|integer|exists:users,id',
            'ptype' => 'required|integer|exists:project_type,id',
            'budget' => 'nullable|numeric',
            'project_currency' => 'nullable|string|max:20',
            'hours' => 'nullable|numeric',
            'services' => 'nullable|string',
            'project_manager' => 'nullable|integer|exists:users,id',
            'contract_copy' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc|max:10240',
        ]);

        if ($validator->fails()) {
            if (!$request->ajax() && !$request->wantsJson()) {
                return redirect()->route('projects.index')
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', $validator->errors()->first());
            }
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();

        $data = [
            'project_id' => $validated['project_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'client_id' => (int) $validated['client_id'],
            'created_by' => auth()->id(),
            'starting_date' => $validated['starting_date'],
            'ending_date' => $validated['ending_date'],
            'actual_starting_date' => $validated['actual_starting_date'] ?? $validated['starting_date'],
            'actual_ending_date' => $validated['actual_ending_date'] ?? $validated['ending_date'],
            'status' => (int) $validated['status'],
            'budget' => array_key_exists('budget', $validated) ? (string) ($validated['budget'] ?? '') : '',
            'ptype' => (int) $validated['ptype'],
            'hours' => (float) ($validated['hours'] ?? 0),
            'project_currency' => $validated['project_currency'] ?? '',
            'manager_id' => $validated['project_manager'] ?? null,
            'is_default' => $request->boolean('is_default') ? 1 : 0,
            'is_visible' => $request->boolean('is_visible') ? 1 : 0,
            'contract_copy' => '',
        ];

        if ($request->hasFile('contract_copy')) {
            $file = $request->file('contract_copy');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $destinationPath = public_path('assets/uploads/projects');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
            $file->move($destinationPath, $filename);
            $data['contract_copy'] = $filename;
        }

        $project = Project::create($data);

        if ($request->filled('services')) {
            $services = collect(explode(',', $request->input('services')))
                ->map(fn($service) => trim($service))
                ->filter()
                ->values();

            foreach ($services as $service) {
                DB::table('services')->insert([
                    'project' => $project->id,
                    'service' => $service,
                    'service_date' => now(),
                ]);
            }
        }

        $assignees = $this->extractProjectUserIds($request);
        if (empty($assignees)) {
            $assignees = [auth()->id()];
        }
        $project->users()->sync($assignees);

        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->route('projects.index')->with('success', 'Project created successfully');
        }

        return response()->json([
            'error' => false,
            'message' => 'Project created successfully',
            'project_id' => $project->id,
        ]);
    }
    
    /**
     * Update project
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->inGroup(1) && !permissions('project_edit')) {
            if (!$request->ajax() && !$request->wantsJson()) {
                return redirect()->route('projects.index')->with('error', 'Access Denied');
            }
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }
        
        $project = Project::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'starting_date' => 'required|date',
            'ending_date' => 'required|date|after_or_equal:starting_date',
            'actual_starting_date' => 'nullable|date',
            'actual_ending_date' => 'nullable|date',
            'status' => 'required|integer|exists:project_status,id',
            'client_id' => 'required|integer|exists:users,id',
            'ptype' => 'required|integer|exists:project_type,id',
            'budget' => 'nullable|numeric',
            'project_currency' => 'nullable|string|max:20',
            'hours' => 'nullable|numeric',
            'services' => 'nullable|string',
            'project_manager' => 'nullable|integer|exists:users,id',
            'contract_copy' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc|max:10240',
        ]);

        if ($validator->fails()) {
            if (!$request->ajax() && !$request->wantsJson()) {
                return redirect()->route('projects.show', $id)
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', $validator->errors()->first());
            }
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'client_id' => (int) $validated['client_id'],
            'starting_date' => $validated['starting_date'],
            'ending_date' => $validated['ending_date'],
            'actual_starting_date' => $validated['actual_starting_date'] ?? $validated['starting_date'],
            'actual_ending_date' => $validated['actual_ending_date'] ?? $validated['ending_date'],
            'status' => (int) $validated['status'],
            'budget' => array_key_exists('budget', $validated) ? (string) ($validated['budget'] ?? '') : '',
            'ptype' => (int) $validated['ptype'],
            'hours' => (float) ($validated['hours'] ?? 0),
            'project_currency' => $validated['project_currency'] ?? '',
            'manager_id' => $validated['project_manager'] ?? null,
            'is_default' => $request->boolean('is_default') ? 1 : 0,
            'is_visible' => $request->boolean('is_visible') ? 1 : 0,
        ];

        if ($request->hasFile('contract_copy')) {
            if (!empty($project->contract_copy)) {
                $oldPath = public_path('assets/uploads/projects/' . $project->contract_copy);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file = $request->file('contract_copy');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $destinationPath = public_path('assets/uploads/projects');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
            $file->move($destinationPath, $filename);
            $data['contract_copy'] = $filename;
        }

        if ($request->filled('services')) {
            DB::table('services')->where('project', $project->id)->delete();

            $services = collect(explode(',', $request->input('services')))
                ->map(fn($service) => trim($service))
                ->filter()
                ->values();

            foreach ($services as $service) {
                DB::table('services')->insert([
                    'project' => $project->id,
                    'service' => $service,
                    'service_date' => now(),
                ]);
            }
        }

        $project->update($data);

        $assignees = $this->extractProjectUserIds($request);
        if (!empty($assignees)) {
            $project->users()->sync($assignees);
        } else {
            $project->users()->syncWithoutDetaching([auth()->id()]);
        }

        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->route('projects.show', $id)->with('success', 'Project updated successfully');
        }

        return response()->json([
            'error' => false,
            'message' => 'Project updated successfully',
        ]);
    }
    
    /**
     * Delete project
     */
    public function destroy($id)
    {
        if (!auth()->user()->inGroup(1) && !permissions('project_delete')) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }
        
        $project = Project::findOrFail($id);
        
        // Check if project has tasks
        if ($project->tasks()->count() > 0) {
            return response()->json([
                'error' => true,
                'message' => 'Tickets exist. Delete request declined !!!'
            ]);
        }
        
        // Delete contract file
        if (!empty($project->contract_copy)) {
            $filePath = public_path('assets/uploads/projects/' . $project->contract_copy);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        // Delete project files
        foreach ($project->files as $file) {
            $uploadedPath = public_path('assets/uploads/projects/' . $file->file_name);
            if (file_exists($uploadedPath)) {
                @unlink($uploadedPath);
            }
            $file->delete();
        }
        
        // Delete relationships
        $project->users()->detach();
        $project->delete();
        
        return response()->json([
            'error' => false,
            'message' => 'Project deleted successfully'
        ]);
    }
    
    /**
     * Upload project file
     */
    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);
        
        $project = Project::findOrFail($id);
        $file = $request->file('file');
        
        $filename = time() . '_' . $file->getClientOriginalName();
        
        // Store in assets/uploads/projects/ (CodeIgniter path)
        $destinationPath = public_path('assets/uploads/projects');
        $file->move($destinationPath, $filename);
        
        $projectFile = MediaFile::create([
            'type' => 'project',
            'type_id' => $project->id,
            'user_id' => auth()->id(),
            'file_name' => $filename,
            'file_size' => $file->getSize(),
            'created' => now(),
        ]);
        
        return response()->json([
            'error' => false,
            'message' => 'File uploaded successfully',
            'file' => $projectFile
        ]);
    }
    
    /**
     * Delete project file
     */
    public function deleteFile($id, $fileId)
    {
        $file = MediaFile::where('type', 'project')
            ->where('type_id', $id)
            ->findOrFail($fileId);
        
        // Delete from assets/uploads/projects/ (CodeIgniter path)
        $filePath = public_path('assets/uploads/projects/' . $file->file_name);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $file->delete();
        
        return response()->json([
            'error' => false,
            'message' => 'File deleted successfully'
        ]);
    }

    private function extractProjectUserIds(Request $request): array
    {
        $rawIds = [];
        if ($request->filled('assigned_consultants')) {
            $rawIds = collect(explode(',', (string) $request->input('assigned_consultants')))
                ->map(fn($id) => (int) trim($id))
                ->filter(fn($id) => $id > 0)
                ->values()
                ->all();
        }

        if (empty($rawIds) && $request->filled('users')) {
            $rawIds = collect(explode(',', (string) $request->input('users')))
                ->map(fn($id) => (int) trim($id))
                ->filter(fn($id) => $id > 0)
                ->values()
                ->all();
        }

        if (empty($rawIds)) {
            return [];
        }

        return User::whereIn('id', $rawIds)->pluck('id')->map(fn($id) => (int) $id)->all();
    }
}
