<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class VerifyEmailController extends Controller
{
    public function show()
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            Log::error('User not found during email verification', [
                'user_id' => $request->route('id')
            ]);
            return redirect()->route('login')
                ->with('error', 'المستخدم غير موجود');
        }

        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            Log::warning('Invalid hash during email verification', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            return redirect()->route('login')
                ->with('error', 'رابط التحقق غير صالح');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')
                ->with('success', 'تم التحقق من البريد الإلكتروني بنجاح');
        }

        try {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            Auth::login($user);

            return redirect()->route('dashboard.index')
                ->with('success', 'تم التحقق من البريد الإلكتروني بنجاح');

        } catch (\Exception $e) {
            Log::error('Error during email verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')
                ->with('error', 'حدث خطأ أثناء التحقق من البريد الإلكتروني');
        }
    }

    public function send(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            if ($user->hasVerifiedEmail()) {
                return redirect()->intended(route('dashboard.index'))
                    ->with('success', 'البريد الإلكتروني مؤكد بالفعل');
            }

            // التحقق من معدل الإرسال
            $key = 'verify-email-' . $user->id;
            $maxAttempts = 3;
            $decayMinutes = 1;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                return back()->with('error', "الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة.");
            }

            RateLimiter::hit($key, $decayMinutes * 60);

            $user->notify(new CustomVerifyEmail);

            Log::info('Verification email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return back()->with('success', 'تم إرسال رابط التحقق بنجاح');

        } catch (\Exception $e) {
            Log::error('Error sending verification email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'حدث خطأ أثناء إرسال رابط التحقق');
        }
    }
    public function resendVerificationEmail()
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email resent successfully.']);
    }
}
