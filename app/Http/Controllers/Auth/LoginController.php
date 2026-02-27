<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class LoginController extends Controller
{
    protected $maxLoginAttempts = 9;
    protected $lockoutTime = 600; // 10 minutes
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('projects.index');
        }
        $data['page_title'] = 'Login - ' . company_name();
        return view('auth.login', $data);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        // Check if too many login attempts
        if ($this->hasTooManyLoginAttempts($request)) {
            return response()->json([
                'error' => true,
                'message' => 'Too many login attempts. Please try again later.',
            ]);
        }
        $user = User::where('email', $request->identity)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->incrementLoginAttempts($request);
            
            return response()->json([
                'error' => true,
                'message' => 'Invalid email or password.',
            ]);
        }
        if (!$user->active) {
            return response()->json([
                'error' => true,
                'message' => 'Your account is not active. Please contact administrator.',
            ]);
        }
        // Update last login
        $user->update([
            'last_login' => time(),
            'ip_address' => $request->ip(),
        ]);
        // Login
        Auth::login($user, $request->filled('remember'));
        // Clear login attempts
        $this->clearLoginAttempts($request);
        return response()->json([
            'error' => false,
            'message' => 'Login successful!',
        ]);
    }
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login.form');
    }
    protected function hasTooManyLoginAttempts(Request $request)
    {
        $ip = $request->ip();
        $time = time() - $this->lockoutTime;
        
        $attempts = LoginAttempt::where('ip_address', $ip)
            ->where('time', '>', $time)
            ->count();
        return $attempts >= $this->maxLoginAttempts;
    }
    protected function incrementLoginAttempts(Request $request)
    {
        LoginAttempt::create([
            'ip_address' => $request->ip(),
            'login' => $request->identity,
            'time' => time(),
        ]);
    }
    protected function clearLoginAttempts(Request $request)
    {
        LoginAttempt::where('ip_address', $request->ip())->delete();
    }
}