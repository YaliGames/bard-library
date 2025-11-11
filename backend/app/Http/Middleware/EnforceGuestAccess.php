<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EnforceGuestAccess
{
    public function handle(Request $request, Closure $next)
    {
        // 预检请求直接放行
        if ($this->isPreflight($request)) return $next($request);

        // 允许游客访问则无需额外限制
        if ($this->getBool('permissions.allow_guest_access', true)) return $next($request);

        // 解析当前用户（优先 Request，再尝试 Sanctum Guard）
        $user = $this->resolveUser($request);
        if ($user) return $next($request);

        // 未登录：仅允许访问白名单公开接口或合法签名链接或有效的资源访问令牌
        if (URL::hasValidSignature($request)) return $next($request);
        if ($this->hasValidResourceToken($request)) return $next($request);
        $patterns = $this->guestWhitelistPatterns();
        if ($this->matchesAny($request, $patterns)) return $next($request);

        return response()->json(['code' => 401, 'data' => null, 'message' => 'Unauthenticated'], 401);
    }

    /** 快速读取布尔型系统设置，带默认值 */
    protected function getBool(string $key, bool $default): bool
    {
        return (bool) SystemSetting::value($key, $default);
    }

    /** 预检请求 */
    protected function isPreflight(Request $request): bool
    {
        return strtoupper($request->getMethod()) === 'OPTIONS';
    }

    /** 解析当前用户（使用 Laravel Session 认证） */
    protected function resolveUser(Request $request)
    {
        if (Auth::check()) {
            return Auth::user();
        }
        return null;
    }

    /** 基于设置构建游客可访问的路径白名单（支持通配） */
    protected function guestWhitelistPatterns(): array
    {
        $allowRegister = $this->getBool('permissions.allow_user_registration', true);
        $allowRecover  = $this->getBool('permissions.allow_recover_password', true);

        $patterns = [
            'api/v1/auth/login',
            'api/v1/settings/public', // 前端需要的公开权限配置
        ];
        if ($allowRegister) {
            $patterns[] = 'api/v1/auth/register';
        }
        if ($allowRecover) {
            $patterns[] = 'api/v1/auth/forgot-password';
            $patterns[] = 'api/v1/auth/reset-password';
        }
        return $patterns;
    }

    /** 请求路径是否匹配任一白名单模式 */
    protected function matchesAny(Request $request, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if ($request->is($p)) return true;
        }
        return false;
    }

    /**
     * 校验全局资源访问令牌（rt/access_token），令牌格式：base64url(payload).hmac
     * payload: { exp: <unix_ts_seconds> }
     */
    protected function hasValidResourceToken(Request $request): bool
    {
        $token = $request->query('rt') ?: $request->query('access_token') ?: $request->header('X-Resource-Token');
        if (!$token || !is_string($token)) return false;
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return false;
        [$p64, $sig] = $parts;
        $payloadJson = $this->b64urlDecode($p64);
        if ($payloadJson === null) return false;
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) return false;
        $exp = isset($payload['exp']) ? intval($payload['exp']) : 0;
        if ($exp <= 0 || time() >= $exp) return false;
        $expected = $this->hmac($p64);
        return hash_equals($expected, $sig);
    }

    protected function hmac(string $data): string
    {
        $key = config('app.key');
        if (is_string($key) && Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        $key = is_string($key) ? $key : '';
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $key, true)), '+/', '-_'), '=');
    }

    protected function b64urlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
