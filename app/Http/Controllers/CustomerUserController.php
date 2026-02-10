<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CustomerUserController extends Controller
{
    /**
     * Display customer users listing page
     */
    public function index()
    {
        $user = auth()->user();
        
        // Only admins can access customer users
        if (!$user->inGroup(1)) {
            abort(403, 'Access Denied');
        }

        // Get list of customers for dropdown (group_id = 3 and is_company = 1)
        $customers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
            ->where('users_groups.group_id', 3)
            ->where('users.is_company', 1)
            ->select('users.id', 'users.company', 'users.first_name', 'users.last_name')
            ->get();

        $data = [
            'page_title' => 'Customer Users - ' . company_name(),
            'current_user' => $user,
            'customers' => $customers,
        ];

        return view('customer-users.index', $data);
    }

    /**
     * Get customer users list via AJAX
     */
    public function getCustomerUsers(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Only admins can view customer users
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $query = User::query()
                ->select('users.*')
                ->join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->whereIn('users_groups.group_id', [3, 4]) // Customer and customer user groups
                ->where('users.is_company', 0); // Only individual users, not companies

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('users.first_name', 'like', "%{$search}%")
                      ->orWhere('users.last_name', 'like', "%{$search}%")
                      ->orWhere('users.email', 'like', "%{$search}%")
                      ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            // Customer filter
            if ($request->filled('customer')) {
                $query->where('users.cuser_customer', $request->customer);
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('users.active', $request->status == 'active' ? 1 : 0);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'id');
            $sortOrder = $request->input('sort_order', 'desc');
            
            $allowedSorts = ['id', 'first_name', 'email', 'created_on'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy("users.{$sortBy}", $sortOrder);
            }

            $customerUsers = $query->get();

            // Format data for response
            $formattedCustomerUsers = $customerUsers->map(function ($cuser) {
                // Get parent customer
                $parentCustomer = User::find($cuser->cuser_customer);
                
                // Build profile picture URL like old project
                $profilePicture = '';
                if (!empty($cuser->profile)) {
                    $profilePicture = asset('uploads/profile/' . $cuser->profile);
                }
                
                // Get project count for this customer user
                $projectCount = 0; // You can implement project counting logic here if needed
                
                return [
                    'id' => $cuser->id,
                    'name' => trim($cuser->first_name . ' ' . $cuser->last_name) ?: 'N/A',
                    'first_name' => $cuser->first_name ?? '',
                    'last_name' => $cuser->last_name ?? '',
                    'email' => $cuser->email ?: 'N/A',
                    'mobile' => $cuser->phone ?: 'N/A',
                    'company' => $parentCustomer ? $parentCustomer->company : 'N/A',
                    'company_id' => $cuser->cuser_customer,
                    'project_count' => $projectCount,
                    'status' => $cuser->active ? 'Active' : 'Inactive',
                    'profile_picture' => $profilePicture,
                    'created_on' => $cuser->created_on ? date('d-M-Y', $cuser->created_on) : 'N/A',
                ];
            });

            return response()->json([
                'error' => false,
                'customer_users' => $formattedCustomerUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching customer users: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error fetching customer users'
            ], 500);
        }
    }

    /**
     * Store a new customer user
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'phone' => 'nullable|string|max:50',
                'cuser_customer' => 'required|exists:users,id',
            ]);

            // Create customer user
            $customerUser = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'cuser_customer' => $request->cuser_customer,
                'is_company' => 0, // Customer user is not a company
                'active' => $request->input('active', 1),
                'created_on' => time(),
                'ip_address' => $request->ip(),
            ]);

            // Assign to customer user group (group_id = 4)
            DB::table('users_groups')->insert([
                'user_id' => $customerUser->id,
                'group_id' => 4,
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Customer user created successfully',
                'customer_user' => $customerUser
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating customer user: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error creating customer user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer user for editing
     */
    public function edit($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $customerUser = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->whereIn('users_groups.group_id', [3, 4])
                ->where('users.id', $id)
                ->where('users.is_company', 0)
                ->select('users.*')
                ->firstOrFail();

            return response()->json([
                'error' => false,
                'customer_user' => $customerUser
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching customer user for edit: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error fetching customer user'
            ], 500);
        }
    }

    /**
     * Update customer user
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $customerUser = User::findOrFail($id);

            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:50',
                'cuser_customer' => 'required|exists:users,id',
            ]);

            // Update customer user
            $customerUser->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'cuser_customer' => $request->cuser_customer,
                'active' => $request->input('active', $customerUser->active),
            ]);

            // Update password if provided
            if ($request->filled('password')) {
                $customerUser->password = Hash::make($request->password);
                $customerUser->save();
            }

            return response()->json([
                'error' => false,
                'message' => 'Customer user updated successfully',
                'customer_user' => $customerUser
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating customer user: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error updating customer user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete customer user
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $customerUser = User::findOrFail($id);

            // Delete user group assignment
            DB::table('users_groups')->where('user_id', $id)->delete();

            // Delete customer user
            $customerUser->delete();

            return response()->json([
                'error' => false,
                'message' => 'Customer user deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting customer user: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error deleting customer user'
            ], 500);
        }
    }
}
