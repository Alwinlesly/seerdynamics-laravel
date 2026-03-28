<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    /**
     * Show current user's profile page.
     */
    public function show()
    {
        $user = auth()->user();

        $group = $user->groups()->first();
        $groupId = $group->id ?? null;
        $roleName = $group ? ucfirst($group->name) : 'User';

        if ($user->inGroup(3)) {
            $projectsCount = DB::table('projects')->where('client_id', $user->id)->count();
        } else {
            $projectsCount = DB::table('project_users')->where('user_id', $user->id)->count();
        }

        $tasksCount = DB::table('task_users')->where('user_id', $user->id)->count();

        $profileUser = [
            'id' => $user->id,
            'email' => $user->email,
            'active' => (int) $user->active,
            'first_name' => $user->first_name ?? '',
            'last_name' => $user->last_name ?? '',
            'company' => $user->company ?? '',
            'phone' => !empty($user->phone) && $user->phone !== '0' ? $user->phone : '',
            'profile' => $user->profile ?? '',
            'short_name' => mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1),
            'role' => $roleName,
            'group_id' => $groupId,
            'projects_count' => $projectsCount,
            'tasks_count' => $tasksCount,
        ];

        return view('users.profile', [
            'page_title' => 'Profile - ' . company_name(),
            'current_user' => $user,
            'profile_user' => $profileUser,
        ]);
    }

    /**
     * Update current user's profile details.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:6|same:password_confirm',
            'password_confirm' => 'nullable|string|min:6',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $update = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? '',
        ];

        if (!empty($validated['password'])) {
            $update['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('profile')) {
            $file = $request->file('profile');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/uploads/profiles'), $filename);

            if (!empty($user->profile) && file_exists(public_path('assets/uploads/profiles/' . $user->profile))) {
                @unlink(public_path('assets/uploads/profiles/' . $user->profile));
            }

            $update['profile'] = $filename;
        }

        User::where('id', $user->id)->update($update);

        return redirect()->route('users.profile')->with('success', 'Profile updated successfully.');
    }
}

