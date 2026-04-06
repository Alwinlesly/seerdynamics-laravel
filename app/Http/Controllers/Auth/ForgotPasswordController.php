<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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
        // Match CI/Ion Auth format: selector.validator
        $selector = bin2hex(random_bytes(10)); // 20 chars
        $validatorToken = bin2hex(random_bytes(40)); // 80 chars
        $code = $selector . '.' . $validatorToken;

        $user->update([
            'forgotten_password_selector' => $selector,
            'forgotten_password_code' => Hash::make($validatorToken),
            'forgotten_password_time' => time(),
        ]);
        // Send email
        $resetLink = url('auth/reset-password/' . $code);
        
        try {
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
        } catch (TransportExceptionInterface $e) {
            \Log::error('Forgot password mail transport failed: ' . $e->getMessage(), [
                'email' => $user->email,
            ]);

            return response()->json([
                'error' => true,
                'data' => '',
                'message' => 'Unable to send reset email right now. Please try again shortly.',
            ], 503);
        }
        return response()->json([
            'error' => false,
            'data' => '',
            'message' => 'Password reset link has been sent to your email.',
        ]);
    }
}
