<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Carbon\Carbon;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'event_type',
        'description',
        'user_id',
        'route',
        'request_data',
        'severity',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'country_code',    // إضافة كود الدولة
        'city',           // إضافة المدينة
        'attack_type',    // نوع الهجوم إن وجد
        'risk_score',     // درجة الخطورة
    ];

    protected $casts = [
        'request_data' => 'encrypted:array', // تشفير البيانات
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'risk_score' => 'integer',
    ];

    // الأحداث المدعومة
    const EVENT_TYPES = [
        'LOGIN_SUCCESS' => 'login',
        'LOGIN_FAILED' => 'failed_login',
        'LOGOUT' => 'logout',
        'PASSWORD_RESET' => 'password_reset',
        'PROFILE_UPDATE' => 'profile_update',
        'SETTINGS_CHANGE' => 'settings_change',
        'API_ACCESS' => 'api_access',
        'SUSPICIOUS_ACTIVITY' => 'suspicious_activity',
        'BLOCKED_ACCESS' => 'blocked_access',
        'DATA_EXPORT' => 'data_export',
        'PERMISSION_CHANGE' => 'permission_change',
        'FILE_ACCESS' => 'file_access',
        'ADMIN_ACTION' => 'admin_action',
    ];

    // مستويات الخطورة
    const SEVERITY_LEVELS = [
        'INFO' => 'info',
        'WARNING' => 'warning',
        'DANGER' => 'danger',
        'CRITICAL' => 'critical',
    ];

    // العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة مع المستخدم الذي قام بحل المشكلة
    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // الحصول على لون نوع الحدث
    public function getEventTypeColorAttribute()
    {
        return match($this->event_type) {
            self::EVENT_TYPES['LOGIN_SUCCESS'] => 'success',
            self::EVENT_TYPES['LOGOUT'] => 'info',
            self::EVENT_TYPES['LOGIN_FAILED'] => 'danger',
            self::EVENT_TYPES['PASSWORD_RESET'] => 'warning',
            self::EVENT_TYPES['PROFILE_UPDATE'] => 'primary',
            self::EVENT_TYPES['SETTINGS_CHANGE'] => 'secondary',
            self::EVENT_TYPES['SUSPICIOUS_ACTIVITY'] => 'danger',
            self::EVENT_TYPES['BLOCKED_ACCESS'] => 'dark',
            self::EVENT_TYPES['DATA_EXPORT'] => 'info',
            default => 'dark'
        };
    }

    // تحليل السلوك المشبوه
    public function analyzeSuspiciousActivity()
    {
        $score = 0;
        
        // تحقق من تكرار نفس IP
        $ipCount = static::where('ip_address', $this->ip_address)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        
        if ($ipCount > 10) $score += 30;
        if ($ipCount > 50) $score += 50;
        
        // تحقق من محاولات تسجيل الدخول الفاشلة
        if ($this->event_type === self::EVENT_TYPES['LOGIN_FAILED']) {
            $failedAttempts = static::where('ip_address', $this->ip_address)
                ->where('event_type', self::EVENT_TYPES['LOGIN_FAILED'])
                ->where('created_at', '>=', now()->subHours(1))
                ->count();
            
            if ($failedAttempts > 5) $score += 40;
            if ($failedAttempts > 20) $score += 60;
        }
        
        // تحقق من الوصول للمسارات الحساسة
        if (str_contains($this->route, 'admin') || str_contains($this->route, 'api')) {
            $score += 20;
        }
        
        $this->risk_score = min($score, 100);
        $this->save();
        
        return $score;
    }

    // تنظيف السجلات القديمة
    public static function cleanOldRecords()
    {
        // احتفظ بالسجلات الخطيرة لمدة سنة
        static::where('severity', '!=', self::SEVERITY_LEVELS['CRITICAL'])
            ->where('created_at', '<=', now()->subMonths(6))
            ->delete();
            
        // احتفظ بالسجلات العادية لمدة 6 أشهر
        static::where('severity', self::SEVERITY_LEVELS['INFO'])
            ->where('created_at', '<=', now()->subMonths(3))
            ->delete();
    }

    // إحصائيات سريعة
    public static function getQuickStats()
    {
        $cacheKey = 'security_logs_stats';
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () {
            return [
                'total_events' => static::count(),
                'critical_events' => static::where('severity', self::SEVERITY_LEVELS['CRITICAL'])->count(),
                'unresolved_issues' => static::where('is_resolved', false)->count(),
                'recent_suspicious' => static::where('event_type', self::EVENT_TYPES['SUSPICIOUS_ACTIVITY'])
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
                'blocked_ips' => BlockedIp::count(),
                'top_attacked_routes' => static::select('route')
                    ->where('severity', '>=', self::SEVERITY_LEVELS['WARNING'])
                    ->groupBy('route')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(5)
                    ->get(),
            ];
        });
    }

    // Scope للأحداث غير المحلولة
    public function scopeUnresolved(Builder $query)
    {
        return $query->where('is_resolved', false);
    }

    // Scope للأحداث الحرجة
    public function scopeCritical(Builder $query)
    {
        return $query->where('severity', self::SEVERITY_LEVELS['CRITICAL']);
    }

    // Scope للأحداث الأخيرة
    public function scopeRecent(Builder $query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
