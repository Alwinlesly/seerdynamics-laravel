<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\MediaFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            $q->whereIn('groups.id', [1, 2]);
        })->get();
        
        // Get project statuses for filter
        $data['project_statuses'] = \App\Models\ProjectStatus::all();
        
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
            if ($user->inGroup(3)) { // Customer
                $query->where('client_id', $user->id);
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
                $query->whereHas('projectStatus', function($q) use ($request) {
                    $q->where('title', $request->status);
                });
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
                    'total_hours' => $project->total_hours ?? 0,
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
        if ($user->inGroup(3) && $project->client_id != $user->id) {
            abort(403, 'Access Denied');
        }
        
        if (!$user->inGroup(1) && !$user->inGroup(3)) {
            if (!$project->users->contains('id', $user->id)) {
                abort(403, 'Access Denied');
            }
        }
        
        $data = [
            'page_title' => 'Project Details - ' . company_name(),
            'current_user' => $user,
            'project' => $project,
        ];
        
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
            if ($user->inGroup(3) && $project->client_id != $user->id) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }
            
            if (!$user->inGroup(1) && !$user->inGroup(3)) {
                if (!$project->users->contains('id', $user->id)) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
            }
            
            // Format project data for the form
            $projectData = [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'services_offered' => $project->services_offered,
                'starting_date' => $project->starting_date ? date('Y-m-d', strtotime($project->starting_date)) : '',
                'ending_date' => $project->ending_date ? date('Y-m-d', strtotime($project->ending_date)) : '',
                'actual_starting_date' => $project->actual_starting_date ? date('Y-m-d', strtotime($project->actual_starting_date)) : '',
                'actual_ending_date' => $project->actual_ending_date ? date('Y-m-d', strtotime($project->actual_ending_date)) : '',
                'project_value' => $project->project_value,
                'project_currency' => $project->project_currency,
                'total_hours' => $project->total_hours,
                'status_title' => $project->projectStatus->title ?? '',
                'project_type' => $project->project_type,
                'client_id' => $project->client_id,
                'is_default' => $project->is_default,
                'is_visible_to_customer' => $project->is_visible_to_customer,
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
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'starting_date' => 'required|date',
            'ending_date' => 'required|date|after:starting_date',
            'actual_starting_date' => 'nullable|date',
            'actual_ending_date' => 'nullable|date',
            'status' => 'required|string',
            'client_id' => 'required|exists:users,id',
            'project_type' => 'nullable|string',
            'project_value' => 'nullable|numeric',
            'project_currency' => 'nullable|string',
            'total_hours' => 'nullable|numeric',
            'services_offered' => 'nullable|string',
        ]);
        
        // Auto-generate project ID
        $latest = Project::latest('id')->first();
        $nextId = ($latest->id ?? 0) + 1;
        $validated['project_id'] = 'PRJ-CR-' . date('Y') . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        
        $validated['created_by'] = auth()->id();
        $validated['is_default'] = $request->has('is_default');
        $validated['is_visible_to_customer'] = !$request->has('is_not_visible_to_customer');
        
        // Handle file upload (contract copy)
        if ($request->hasFile('contract_copy')) {
            $file = $request->file('contract_copy');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('projects/contracts', $filename, 'public');
            $validated['contract_copy'] = $path;
        }
        
        $project = Project::create($validated);
        
        // Assign project users if provided
        if ($request->has('assigned_consultants')) {
            $consultantIds = explode(',', $request->assigned_consultants);
            $project->users()->sync(array_filter($consultantIds));
        }
        
        return response()->json([
            'error' => false,
            'message' => 'Project created successfully',
            'project_id' => $project->id
        ]);
    }
    
    /**
     * Update project
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->inGroup(1) && !permissions('project_edit')) {
            return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
        }
        
        $project = Project::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'starting_date' => 'required|date',
            'ending_date' => 'required|date',
            'status' => 'required|string',
            'client_id' => 'required|exists:users,id',
        ]);
        
        if ($request->hasFile('contract_copy')) {
            // Delete old file
            if ($project->contract_copy) {
                Storage::disk('public')->delete($project->contract_copy);
            }
            
            $file = $request->file('contract_copy');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('projects/contracts', $filename, 'public');
            $validated['contract_copy'] = $path;
        }
        
        $validated['is_default'] = $request->has('is_default');
        $validated['is_visible_to_customer'] = !$request->has('is_not_visible_to_customer');
        
        $project->update($validated);
        
        if ($request->has('assigned_consultants')) {
            $consultantIds = explode(',', $request->assigned_consultants);
            $project->users()->sync(array_filter($consultantIds));
        }
        
        return response()->json([
            'error' => false,
            'message' => 'Project updated successfully'
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
        if ($project->contract_copy) {
            Storage::disk('public')->delete($project->contract_copy);
        }
        
        // Delete project files
        foreach ($project->files as $file) {
            Storage::disk('public')->delete('projects/' . $file->file_name);
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
}
