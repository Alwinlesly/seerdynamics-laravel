<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class ResetPasswordController extends Controller
{
    public function showResetForm($code)
    {
        $user = User::where('forgotten_password_code', $code)
            ->where('forgotten_password_time', '>', time() - 1800) // 30 minutes
            ->first();
        if (!$user) {
            return redirect()->route('login.form')
                ->with('message', 'Invalid or expired reset code.');
        }
        $data['page_title'] = 'Reset Password - ' . company_name();
        $data['code'] = $code;
        $data['user_id'] = $user->id;
        
        return view('auth.reset', $data);
    }
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new' => 'required|min:8|confirmed',
            'new_confirmation' => 'required',
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $user = User::where('forgotten_password_code', $request->code)
            ->where('forgotten_password_time', '>', time() - 1800)
            ->first();
        if (!$user) {
            return back()->with('message', 'Invalid or expired reset code.');
        }
        // Update password
        $user->update([
            'password' => Hash::make($request->new),
            'forgotten_password_code' => null,
            'forgotten_password_time' => null,
            'active' => 1,
        ]);
        return redirect()->route('login.form')
            ->with('message', 'Password reset successful! You can now login.')
            ->with('message_type', 'success');
    }
}