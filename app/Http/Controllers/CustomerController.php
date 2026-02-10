<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class CustomerController extends Controller
{
    /**
     * Display customers listing page
     */
    public function index()
    {
        $user = auth()->user();
        
        // Only admins can access customers
        if (!$user->inGroup(1)) {
            abort(403, 'Access Denied');
        }

        $data = [
            'page_title' => 'Customers - ' . company_name(),
            'current_user' => $user,
        ];

        return view('customers.index', $data);
    }

    /**
     * Get customers list with filters (AJAX)
     */
    public function getCustomers(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Only admins can view customers
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $query = User::query()
                ->select('users.*')
                ->join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->where('users_groups.group_id', 3) // Customer group
                ->where('users.is_company', 1); // Only companies, not individual users

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('users.first_name', 'like', "%{$search}%")
                      ->orWhere('users.last_name', 'like', "%{$search}%")
                      ->orWhere('users.email', 'like', "%{$search}%")
                      ->orWhere('users.company', 'like', "%{$search}%")
                      ->orWhere('users.phone', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('users.active', $request->status == 'active' ? 1 : 0);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'id');
            $sortOrder = $request->input('sort_order', 'desc');
            
            $allowedSorts = ['id', 'first_name', 'email', 'company', 'created_on'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy("users.{$sortBy}", $sortOrder);
            }

            $customers = $query->get();


            // Format data for response
            $formattedCustomers = $customers->map(function ($customer) {
                // Get project count
                $projectCount = Project::where('client_id', $customer->id)->count();
                
                // Build profile picture URL like old project
                $profilePicture = '';
                if (!empty($customer->profile)) {
                    $profilePicture = asset('uploads/profile/' . $customer->profile);
                }
                
                // Get country name from countries table
                $countryName = '';
                if (!empty($customer->country)) {
                    $country = \App\Models\Country::find($customer->country);
                    $countryName = $country ? $country->name : '';
                }
                
                return [
                    'id' => $customer->id,
                    'name' => $customer->customer_code . ' - ' . $customer->company, // Show customer_code - company
                    'customer_code' => $customer->customer_code ?? '',
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email ?? 'N/A',
                    'company' => $customer->company ?? 'N/A',
                    'contact_person' => ($customer->contact_person_desg ?? '') . ' ' . ($customer->first_name ?? ''), // Designation + POC name
                    'contact_person_desg' => $customer->contact_person_desg ?? 'N/A',
                    'address' => ($customer->address ?? '') . '-' . $countryName, // Address-Country Name
                    'phone' => $customer->phone ?? 'N/A',
                    'project_count' => $projectCount,
                    'status' => $customer->active ? 'Active' : 'Inactive',
                    'profile_picture' => $profilePicture,
                    'created_on' => $customer->created_on ? date('d-M-Y', $customer->created_on) : 'N/A',
                ];
            });

            return response()->json([
                'error' => false,
                'customers' => $formattedCustomers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching customers: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error fetching customers'
            ], 500);
        }
    }

    /**
     * Store new customer
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $request->validate([
                'company' => 'required|string|max:255',
                'customer_code' => 'required|string|max:100',
                'first_name' => 'required|string|max:255',
                'contact_person_desg' => 'required|string|max:255',
                'address' => 'required|string',
                'country' => 'required',
                'email' => 'required|email|unique:users,email',
                'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Handle profile image upload
            $profileFilename = null;
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $profileFilename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/profile'), $profileFilename);
            }

            // Create customer
            $customer = User::create([
                'company' => $request->company,
                'customer_code' => $request->customer_code,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name ?? '',
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'thisisdefcust'),
                'phone' => $request->phone ?? '',
                'profile' => $profileFilename,
                'address' => $request->address,
                'contact_person_desg' => $request->contact_person_desg,
                'country' => $request->country,
                'is_company' => 1, // Mark as company customer
                'active' => $request->input('active', 1),
                'created_on' => time(),
                'ip_address' => $request->ip(),
            ]);

            // Assign to customer group (group_id = 3)
            DB::table('users_groups')->insert([
                'user_id' => $customer->id,
                'group_id' => 3,
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Customer created successfully',
                'customer' => $customer
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating customer: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error creating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show customer details
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                if (request()->ajax()) {
                    return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
                }
                abort(403, 'Access Denied');
            }

            $customer = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->where('users_groups.group_id', 3)
                ->where('users.id', $id)
                ->select('users.*')
                ->firstOrFail();

            // Get customer projects
            $projects = Project::where('client_id', $id)->get();

            if (request()->ajax()) {
                $html = view('customers.partials.detail-content', [
                    'customer' => $customer,
                    'projects' => $projects
                ])->render();
                
                return response()->json([
                    'error' => false,
                    'html' => $html
                ]);
            }

            $data = [
                'page_title' => 'Customer Details - ' . company_name(),
                'current_user' => $user,
                'customer' => $customer,
                'projects' => $projects,
            ];

            return view('customers.show', $data);

        } catch (\Exception $e) {
            \Log::error('Error showing customer: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Error loading customer details'
                ], 500);
            }
            
            return back()->with('error', 'Error loading customer details');
        }
    }

    /**
     * Get customer data for editing
     */
    public function edit($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $customer = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->where('users_groups.group_id', 3)
                ->where('users.id', $id)
                ->select('users.*')
                ->firstOrFail();

            return response()->json([
                'error' => false,
                'customer' => $customer
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching customer for edit: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error fetching customer'
            ], 500);
        }
    }

    /**
     * Update customer
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $customer = User::findOrFail($id);

            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|min:6',
                'company' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'contact_person_desg' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:100',
            ]);

            $updateData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'company' => $request->company,
                'phone' => $request->phone,
                'address' => $request->address,
                'contact_person_desg' => $request->contact_person_desg,
                'country' => $request->country,
                'active' => $request->input('active', 1),
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $customer->update($updateData);

            return response()->json([
                'error' => false,
                'message' => 'Customer updated successfully',
                'customer' => $customer
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating customer: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error updating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete customer
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return response()->json(['error' => true, 'message' => 'Access Denied'], 403);
            }

            $customer = User::findOrFail($id);
            
            // Check if customer has projects
            $projectCount = Project::where('client_id', $id)->count();
            if ($projectCount > 0) {
                return response()->json([
                    'error' => true,
                    'message' => "Cannot delete customer with {$projectCount} active project(s)"
                ], 400);
            }

            // Delete user-group relationship
            DB::table('users_groups')->where('user_id', $id)->delete();
            
            // Delete customer
            $customer->delete();

            return response()->json([
                'error' => false,
                'message' => 'Customer deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting customer: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error deleting customer'
            ], 500);
        }
    }

    /**
     * Export customers to CSV
     */
    public function export()
    {
        try {
            $user = auth()->user();
            
            if (!$user->inGroup(1)) {
                return back()->with('error', 'Access Denied');
            }

            $customers = User::join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->where('users_groups.group_id', 3)
                ->where('users.is_company', 1) // Only companies
                ->select('users.*')
                ->orderBy('users.id', 'desc')
                ->get();

            $filename = 'customers_' . date('Y-m-d_His') . '.csv';
            $handle = fopen('php://output', 'w');
            
            ob_start();
            
            // Headers
            fputcsv($handle, [
                'ID',
                'First Name',
                'Last Name',
                'Email',
                'Company',
                'Phone',
                'Contact Person',
                'Address',
                'Country',
                'Status',
                'Created On'
            ]);

            // Data
            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->id,
                    $customer->first_name,
                    $customer->last_name,
                    $customer->email,
                    $customer->company,
                    $customer->phone,
                    $customer->contact_person_desg,
                    $customer->address,
                    $customer->country,
                    $customer->active ? 'Active' : 'Inactive',
                    $customer->created_on ? date('d-M-Y', $customer->created_on) : '',
                ]);
            }

            fclose($handle);
            $csv = ob_get_clean();

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename={$filename}");

        } catch (\Exception $e) {
            \Log::error('Error exporting customers: ' . $e->getMessage());
            return back()->with('error', 'Error exporting customers');
        }
    }
}
