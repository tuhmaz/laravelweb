<?php

namespace App\Models;

use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    protected $guard_name = 'sanctum';

    private static $isVerificationEmailSent = false;

    public function isAdmin()
    {
         return $this->is_admin;
    }

    /**
     * إرسال إشعار تأكيد البريد الإلكتروني.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        Log::info('Sending email verification notification', [
            'user_id' => $this->id,
            'email' => $this->email
        ]);
        
        $this->notify(new CustomVerifyEmail);
        
        Log::info('Email verification notification sent successfully');
    }

    /**
     * Get the user's avatar URL.
     * Returns profile photo if exists, otherwise returns a random avatar.
     *
     * @return string
     */
    public function getAvatarUrl()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        $randomNumber = ($this->id % 8) + 1;
        return asset("assets/img/avatars/{$randomNumber}.png");
    }

    /**
     * تحقق مما إذا كان المستخدم متصل حالياً
     *
     * @return bool
     */
    public function isOnline()
    {
        return $this->last_seen >= now()->subMinutes(5);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'job_title',
        'gender',
        'country',
        'bio',
        'social_links',
        'last_seen',
        'is_online',
        'google_id',
        'profile_photo_path'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'social_links' => 'json',
        'last_seen' => 'datetime',
        'is_online' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];
}
