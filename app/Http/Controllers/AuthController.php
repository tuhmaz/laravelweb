<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('content.authentications.auth-login-cover');
    }

    public function showRegistrationForm()
    {
        return view('content.authentications.auth-register-cover');
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // تسجيل إنشاء المستخدم
            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // إرسال إشعار التحقق من البريد
            try {
                $user->notify(new CustomVerifyEmail);
                Log::info('Verification email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }

            event(new Registered($user));

            Auth::login($user);

            return redirect()->route('verification.notice')
                ->with('success', __('تم إنشاء حسابك بنجاح. يرجى التحقق من بريدك الإلكتروني لتفعيل حسابك.'));
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => __('حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.')]);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        // حفظ قاعدة البيانات الحالية
        $currentDatabase = session('database', config('database.default'));

        // تبديل إلى قاعدة البيانات الرئيسية للتحقق من المستخدم
        \DB::setDefaultConnection('mysql');

        // تسجيل محاولة تسجيل الدخول
        \Log::info('Login attempt', [
            'email' => $request->email,
            'original_database' => $currentDatabase,
            'auth_database' => \DB::connection()->getDatabaseName()
        ]);

        // التحقق من وجود المستخدم في قاعدة البيانات الرئيسية
        $user = \App\Models\User::where('email', $request->email)->first();
        if (!$user) {
            \Log::warning('User not found', ['email' => $request->email]);

            // إعادة تعيين قاعدة البيانات إلى القيمة الأصلية
            \DB::setDefaultConnection($currentDatabase);

            return back()->withErrors([
                'email' => 'البريد الإلكتروني غير مسجل في النظام.'
            ]);
        }

        // محاولة تسجيل الدخول
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // تسجيل نجاح تسجيل الدخول
            \Log::info('Login successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // إعادة تعيين قاعدة البيانات إلى القيمة الأصلية
            \DB::setDefaultConnection($currentDatabase);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'user' => $user
                ]);
            }

            return redirect()->intended('/');
        }

        // تسجيل فشل تسجيل الدخول
        \Log::warning('Login failed - Invalid password', [
            'email' => $request->email
        ]);

        // إعادة تعيين قاعدة البيانات إلى القيمة الأصلية
        \DB::setDefaultConnection($currentDatabase);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'كلمة المرور غير صحيحة'
            ], 401);
        }

        return back()->withErrors([
            'email' => 'كلمة المرور غير صحيحة.'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);
        }

        return redirect('/');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($request->wantsJson()) {
            return $status === Password::RESET_LINK_SENT
                ? response()->json(['status' => true, 'message' => __($status)])
                : response()->json(['status' => false, 'message' => __($status)], 400);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showForgotPasswordForm()
    {
        return view('content.authentications.auth-forgot-password-cover'); 
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($request->wantsJson()) {
            return $status === Password::PASSWORD_RESET
                ? response()->json(['status' => true, 'message' => __($status)])
                : response()->json(['status' => false, 'message' => __($status)], 400);
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showResetPasswordForm($token)
    {
        return view('content.authentications.auth-reset-password-cover', ['token' => $token]);
    }

    public function verify(Request $request)
    {
        try {
            $user = User::find($request->route('id'));

            if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
                throw new AuthorizationException;
            }

            if ($user->hasVerifiedEmail()) {
                return redirect()->intended(route('dashboard.index').'?verified=1');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return redirect()->intended(route('dashboard.index').'?verified=1');
            
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'user_id' => $request->route('id'),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('verification.notice')
                ->with('error', __('فشل التحقق من البريد الإلكتروني. يرجى المحاولة مرة أخرى.'));
        }
    }

    public function verificationNotice()
    {
        return view('content.authentications.auth-verify-email-cover');
    }

    public function verificationResend(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => __('البريد الإلكتروني مؤكد بالفعل.')
                    ], 400);
                }
                return back()->with('error', __('البريد الإلكتروني مؤكد بالفعل.'));
            }

            // التحقق من معدل الإرسال
            $key = 'verify-email-' . $request->user()->id;
            $maxAttempts = 3;
            $decayMinutes = 1;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة.")
                    ], 429);
                }
                return back()->with('error', __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة."));
            }

            RateLimiter::hit($key, $decayMinutes * 60);

            $request->user()->notify(new CustomVerifyEmail);

            Log::info('Verification email resent', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => __('تم إرسال رابط التحقق بنجاح.')
                ]);
            }

            return back()->with('success', __('تم إرسال رابط التحقق بنجاح.'));

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'user_id' => $request->user()->id ?? 'unknown',
                'email' => $request->user()->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => __('حدث خطأ أثناء إرسال رابط التحقق.')
                ], 500);
            }

            return back()->with('error', __('حدث خطأ أثناء إرسال رابط التحقق.'));
        }
    }

    public function showVerificationNotice()
    {
        return view('content.authentications.auth-verify-email-cover');
    }

    public function resendVerificationEmail(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => __('البريد الإلكتروني مؤكد بالفعل.')
                ], 400);
            }

            // التحقق من معدل الإرسال
            $key = 'verify-email-' . $request->user()->id;
            $maxAttempts = 3;
            $decayMinutes = 1;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'status' => false,
                    'message' => __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة.")
                ], 429);
            }

            RateLimiter::hit($key, $decayMinutes * 60);

            $request->user()->notify(new CustomVerifyEmail);

            Log::info('Verification email resent', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            return response()->json([
                'status' => true,
                'message' => __('تم إرسال رابط التحقق بنجاح.')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => __('حدث خطأ أثناء إرسال رابط التحقق.')
            ], 500);
        }
    }
}
