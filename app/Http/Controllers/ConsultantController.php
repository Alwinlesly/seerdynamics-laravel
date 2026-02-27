<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConsultantController extends Controller
{
    /**
     * Display consultants list
     */
    public function index()
    {
        $user = auth()->user();
        
        // Only admins can access
        if (!$user->inGroup(1)) {
            return redirect()->route('home');
        }
        
        // Get consultants (group 2 users)
        $consultants = DB::table('users')
            ->join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->where('users_groups.group_id', 2)
            ->select('users.*', 'users_groups.group_id')
            ->get();
        
        $systemUsers = [];
        foreach ($consultants as $consultant) {
            $group = DB::table('users_groups')
                ->join('groups', 'groups.id', '=', 'users_groups.group_id')
                ->where('users_groups.user_id', $consultant->id)
                ->select('groups.id', 'groups.name')
                ->first();
            
            $projectsCount = DB::table('project_users')
                ->where('user_id', $consultant->id)
                ->count();
            
            $tasksCount = DB::table('task_users')
                ->where('user_id', $consultant->id)
                ->count();
            
            $systemUsers[] = [
                'id' => $consultant->id,
                'first_name' => $consultant->first_name,
                'last_name' => $consultant->last_name,
                'email' => $consultant->email,
                'phone' => $consultant->phone != 0 ? $consultant->phone : 'No Number',
                'active' => $consultant->active,
                'profile' => !empty($consultant->profile) ? asset('assets/uploads/profiles/' . $consultant->profile) : '',
                'short_name' => mb_substr($consultant->first_name, 0, 1) . mb_substr($consultant->last_name ?? '', 0, 1),
                'role' => $group ? ucfirst($group->name) : 'Consultant',
                'group_id' => $group ? $group->id : 2,
                'projects_count' => $projectsCount,
                'tasks_count' => $tasksCount,
            ];
        }
        
        $data = [
            'page_title' => 'Consultants - ' . company_name(),
            'current_user' => $user,
            'system_users' => $systemUsers,
        ];
        
        return view('consultants.index', $data);
    }
    
    /**
     * Create a new consultant
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->inGroup(1)) {
            return response()->json(['error' => true, 'message' => 'Access Denied']);
        }
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);
        
        try {
            $newUser = new User();
            $newUser->first_name = $request->first_name;
            $newUser->last_name = $request->last_name;
            $newUser->email = strtolower($request->email);
            $newUser->phone = $request->phone;
            $newUser->password = Hash::make($request->password);
            $newUser->active = 1;
            $newUser->ip_address = $request->ip();
            $newUser->created_on = time();
            $newUser->save();
            
            // Add to group 2 (consultant)
            DB::table('users_groups')->insert([
                'user_id' => $newUser->id,
                'group_id' => 2,
            ]);
            
            return response()->json([
                'error' => false,
                'message' => 'Consultant created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get consultant by ID for edit
     */
    public function getById($id)
    {
        $user = auth()->user();
        
        if (!$user->inGroup(1)) {
            return response()->json(['error' => true, 'message' => 'Access Denied']);
        }
        
        $consultant = User::find($id);
        if (!$consultant) {
            return response()->json(['error' => true, 'message' => 'No user found.']);
        }
        
        $group = DB::table('users_groups')
            ->join('groups', 'groups.id', '=', 'users_groups.group_id')
            ->where('users_groups.user_id', $id)
            ->select('groups.id', 'groups.name')
            ->first();
        
        return response()->json([
            'error' => false,
            'data' => [
                'id' => $consultant->id,
                'first_name' => $consultant->first_name,
                'last_name' => $consultant->last_name,
                'email' => $consultant->email,
                'phone' => $consultant->phone,
                'active' => $consultant->active,
                'role' => $group ? ucfirst($group->name) : 'Consultant',
                'group_id' => $group ? $group->id : 2,
            ]
        ]);
    }
    
    /**
     * Update consultant
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $id = $request->input('update_id');
        
        if (!$user->inGroup(1) && $user->id != $id) {
            return response()->json(['error' => true, 'message' => 'Access Denied']);
        }
        
        $request->validate([
            'update_id' => 'required|numeric',
            'first_name' => 'required|string|max:255',
        ]);
        
        try {
            $consultant = User::findOrFail($id);
            $consultant->first_name = $request->first_name;
            $consultant->last_name = $request->last_name;
            $consultant->phone = $request->phone;
            
            if ($request->filled('password')) {
                $consultant->password = Hash::make($request->password);
            }
            
            $consultant->save();
            
            return response()->json([
                'error' => false,
                'message' => 'Consultant updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete consultant
     */
    public function destroy($id)
    {
        $user = auth()->user();
        
        if (!$user->inGroup(1)) {
            return response()->json(['error' => true, 'message' => 'Access Denied']);
        }
        
        // Check if user has projects
        $hasProjects = DB::table('project_users')->where('user_id', $id)->exists();
        if ($hasProjects) {
            return response()->json([
                'error' => true,
                'message' => 'Projects available for this consultant. Delete request declined!'
            ]);
        }
        
        try {
            // Delete related records
            DB::table('project_users')->where('user_id', $id)->delete();
            DB::table('task_users')->where('user_id', $id)->delete();
            
            User::destroy($id);
            
            return response()->json([
                'error' => false,
                'message' => 'Consultant deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Activate consultant
     */
    public function activate($id)
    {
        $user = auth()->user();
        
        if (!$user->inGroup(1)) {
            return response()->json(['error' => true, 'message' => 'Access Denied']);
        }
        
        User::where('id', $id)->update(['active' => 1]);
        
        return response()->json([
            'error' => false,
            'message' => 'Consultant activated successfully'
        ]);
    }
    
    /**
     * Deactivate consultant
     */
    public function deactivate($id)
    {
        $user = auth()->user();
        
        if (!$user->inGroup(1)) {
            return response()->json(['error' => true, 'message' => 'Access Denied']);
        }
        
        User::where('id', $id)->update(['active' => 0]);
        
        return response()->json([
            'error' => false,
            'message' => 'Consultant deactivated successfully'
        ]);
    }
}
