<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 登录限流器
 */
class LoginThrottler
{
    /**
     * 记录登录失败
     *
     * @param string $identifier 标识符（通常是邮箱或IP）
     * @return void
     */
    public static function recordFailedAttempt(string $identifier): void
    {
        $key = self::getCacheKey($identifier);
        $attempts = Cache::get($key, 0);
        $attempts++;
        
        $lockoutDuration = SystemSetting::value('security.lockout_duration', 15);
        $expiresAt = now()->addMinutes($lockoutDuration);
        
        // 保存失败次数和过期时间
        Cache::put($key, $attempts, $expiresAt);
        Cache::put($key . ':expires_at', $expiresAt->timestamp, $expiresAt);
    }
    
    /**
     * 清除失败记录
     *
     * @param string $identifier
     * @return void
     */
    public static function clearAttempts(string $identifier): void
    {
        $key = self::getCacheKey($identifier);
        Cache::forget($key);
        Cache::forget($key . ':expires_at');
    }
    
    /**
     * 检查是否被锁定
     *
     * @param string $identifier
     * @return bool
     */
    public static function isLocked(string $identifier): bool
    {
        $maxAttempts = SystemSetting::value('security.max_login_attempts', 5);
        $attempts = self::getAttempts($identifier);
        
        return $attempts >= $maxAttempts;
    }
    
    /**
     * 获取当前失败次数
     *
     * @param string $identifier
     * @return int
     */
    public static function getAttempts(string $identifier): int
    {
        $key = self::getCacheKey($identifier);
        return Cache::get($key, 0);
    }
    
    /**
     * 获取剩余尝试次数
     *
     * @param string $identifier
     * @return int
     */
    public static function getRemainingAttempts(string $identifier): int
    {
        $maxAttempts = SystemSetting::value('security.max_login_attempts', 5);
        $attempts = self::getAttempts($identifier);
        
        return max(0, $maxAttempts - $attempts);
    }
    
    /**
     * 获取锁定剩余时间（秒）
     *
     * @param string $identifier
     * @return int|null 如果未锁定返回null
     */
    public static function getLockoutSeconds(string $identifier): ?int
    {
        if (!self::isLocked($identifier)) {
            return null;
        }
        
        $key = self::getCacheKey($identifier);
        $expiresAtKey = $key . ':expires_at';
        $expiresAtTimestamp = Cache::get($expiresAtKey);
        
        if (!$expiresAtTimestamp) {
            // 如果没有过期时间记录，返回默认锁定时长
            $lockoutDuration = SystemSetting::value('security.lockout_duration', 15);
            return $lockoutDuration * 60;
        }
        
        // 计算当前时间到过期时间的秒数
        $seconds = $expiresAtTimestamp - time();
        return max(0, $seconds);
    }
    
    /**
     * 获取缓存键
     *
     * @param string $identifier
     * @return string
     */
    private static function getCacheKey(string $identifier): string
    {
        return 'login_attempts:' . sha1($identifier);
    }
    
    /**
     * 获取错误消息
     *
     * @param string $identifier
     * @return string
     */
    public static function getErrorMessage(string $identifier): string
    {
        $seconds = self::getLockoutSeconds($identifier);
        if ($seconds === null) {
            return '登录失败次数过多';
        }
        
        $minutes = ceil($seconds / 60);
        return "登录失败次数过多，请在 {$minutes} 分钟后重试";
    }
}
