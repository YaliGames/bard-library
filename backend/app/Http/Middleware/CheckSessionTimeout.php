<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

/**
 * 会话超时检查中间件
 */
class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 如果未登录，直接放行
        if (!Auth::check()) {
            return $next($request);
        }
        
        // 获取会话超时设置（分钟）
        $timeoutMinutes = SystemSetting::value('security.session_timeout', 120);
        
        // 获取最后活动时间
        $lastActivity = $request->session()->get('last_activity');
        
        if ($lastActivity) {
            $elapsed = time() - $lastActivity;
            $timeoutSeconds = $timeoutMinutes * 60;
            
            // 如果超时，登出用户
            if ($elapsed > $timeoutSeconds) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return response()->json([
                    'message' => '会话已过期，请重新登录',
                    'code' => 'SESSION_TIMEOUT'
                ], 401);
            }
        }
        
        // 更新最后活动时间
        $request->session()->put('last_activity', time());
        
        return $next($request);
    }
}
