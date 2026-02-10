<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'data' => '',
                'message' => $validator->errors()->first(),
            ]);
        }
        $user = User::where('email', $request->identity)->first();
        if (!$user) {
            return response()->json([
                'error' => true,
                'data' => '',
                'message' => 'Email not found.',
            ]);
        }
        // Generate reset code
        $code = Str::random(40);
        $user->update([
            'forgotten_password_code' => $code,
            'forgotten_password_time' => time(),
        ]);
        // Send email
        $resetLink = url('auth/reset-password/' . $code);
        
        Mail::send([], [], function ($message) use ($user, $resetLink) {
            $message->to($user->email)
                ->subject('Password Reset - ' . company_name())
                ->html("
                    <html>
                    <body>
                        <p>You requested a password reset for " . company_name() . ".</p>
                        <p><a href='{$resetLink}'>Click here to reset your password</a></p>
                        <p>Or copy this link: {$resetLink}</p>
                        <p>This link will expire in 30 minutes.</p>
                    </body>
                    </html>
                ");
        });
        return response()->json([
            'error' => false,
            'data' => '',
            'message' => 'Password reset link has been sent to your email.',
        ]);
    }
}