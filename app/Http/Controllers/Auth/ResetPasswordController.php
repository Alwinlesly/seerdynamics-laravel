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
        $user = $this->getUserFromResetCode($code);

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
        $user = $this->getUserFromResetCode($request->code);

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

    private function getUserFromResetCode($code)
    {
        $normalizedCode = $this->normalizeResetCode($code);
        if (!$normalizedCode) {
            return null;
        }

        $parts = explode('.', $normalizedCode, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $validatorToken] = $parts;
        $user = User::where('forgotten_password_selector', $selector)->first();
        if (!$user) {
            return null;
        }

        if (empty($user->forgotten_password_code) || empty($user->forgotten_password_time)) {
            return null;
        }

        // Keep expiration same as existing CI project (30 minutes).
        if ((time() - (int) $user->forgotten_password_time) > 1800) {
            return null;
        }

        if (!Hash::check($validatorToken, $user->forgotten_password_code)) {
            return null;
        }

        return $user;
    }

    private function normalizeResetCode($code)
    {
        if (!is_string($code) || trim($code) === '') {
            return null;
        }

        $code = trim(urldecode($code));

        if (preg_match('/^[A-Fa-f0-9]+\.[A-Fa-f0-9]+$/', $code)) {
            return $code;
        }

        if (preg_match('/^([A-Fa-f0-9]+)\.([A-Fa-f0-9]+)\.[A-Za-z0-9_-]+$/', $code, $matches)) {
            return $matches[1] . '.' . $matches[2];
        }

        return $code;
    }
}
