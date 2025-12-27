<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\PasswordValidator;
use App\Support\ApiHelpers;

class SecurityController extends Controller
{
    /**
     * 获取登录尝试历史
     */
    public function getLoginAttempts(Request $request)
    {
        $query = DB::table('login_attempts')
            ->orderBy('attempted_at', 'desc');
        
        // 筛选条件
        if ($request->has('identifier')) {
            $query->where('identifier', 'like', '%' . $request->identifier . '%');
        }
        
        if ($request->has('success')) {
            $query->where('success', $request->success === 'true' || $request->success === '1');
        }
        
        if ($request->has('start_date')) {
            $query->where('attempted_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('attempted_at', '<=', $request->end_date);
        }
        
        // 分页
        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);
        
        $total = $query->count();
        $items = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        return ApiHelpers::success(['data' => $items, 'total' => $total, 'page' => (int)$page, 'per_page' => (int)$perPage, 'last_page' => ceil($total / $perPage)], '', 200);
    }
    
    /**
     * 获取登录统计
     */
    public function getLoginStats(Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days)->startOfDay();
        
        // 总登录次数
        $totalAttempts = DB::table('login_attempts')
            ->where('attempted_at', '>=', $startDate)
            ->count();
        
        // 成功登录次数
        $successfulLogins = DB::table('login_attempts')
            ->where('attempted_at', '>=', $startDate)
            ->where('success', true)
            ->count();
        
        // 失败登录次数
        $failedLogins = DB::table('login_attempts')
            ->where('attempted_at', '>=', $startDate)
            ->where('success', false)
            ->count();
        
        // 独立用户数
        $uniqueUsers = DB::table('login_attempts')
            ->where('attempted_at', '>=', $startDate)
            ->where('success', true)
            ->distinct('identifier')
            ->count('identifier');
        
        // 按日期分组统计
        $dailyStats = DB::table('login_attempts')
            ->select(
                DB::raw('DATE(attempted_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed')
            )
            ->where('attempted_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(attempted_at)'))
            ->orderBy('date', 'asc')
            ->get();
        
        // 失败原因统计
        $failureReasons = DB::table('login_attempts')
            ->select('failure_reason', DB::raw('COUNT(*) as count'))
            ->where('attempted_at', '>=', $startDate)
            ->where('success', false)
            ->whereNotNull('failure_reason')
            ->groupBy('failure_reason')
            ->orderBy('count', 'desc')
            ->get();
        
        return ApiHelpers::success(['summary' => ['total_attempts' => $totalAttempts, 'successful_logins' => $successfulLogins, 'failed_logins' => $failedLogins, 'unique_users' => $uniqueUsers, 'success_rate' => $totalAttempts > 0 ? round(($successfulLogins / $totalAttempts) * 100, 2) : 0], 'daily_stats' => $dailyStats, 'failure_reasons' => $failureReasons], '', 200);
    }
    
    /**
     * 清除登录尝试历史
     */
    public function clearLoginAttempts(Request $request)
    {
        $days = $request->get('days', 30);
        
        $deleted = DB::table('login_attempts')
            ->where('attempted_at', '<', now()->subDays($days))
            ->delete();
        
        return ApiHelpers::success(['deleted' => $deleted], "已清除 {$days} 天前的 {$deleted} 条记录", 200);
    }
    
    /**
     * 解锁账户（清除登录限制）
     */
    public function unlockAccount(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
        ]);
        
        \App\Support\LoginThrottler::clearAttempts($data['identifier']);
        
        return ApiHelpers::success(null, '账户已解锁', 200);
    }
    
    /**
     * 获取密码策略信息
     */
    public function getPasswordPolicy()
    {
        return ApiHelpers::success(['description' => PasswordValidator::getRulesDescription(), 'rules' => ['min_length' => \App\Models\SystemSetting::value('security.password_min_length', 6), 'require_strong' => \App\Models\SystemSetting::value('security.require_strong_password', false)]], '', 200);
    }
    
    /**
     * 验证密码强度
     */
    public function validatePassword(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);
        
        $result = PasswordValidator::validate($data['password']);
        
        return ApiHelpers::success(['valid' => $result['valid'], 'errors' => $result['errors']], $result['valid'] ? '密码符合要求' : '密码不符合要求', 200);
    }
}
