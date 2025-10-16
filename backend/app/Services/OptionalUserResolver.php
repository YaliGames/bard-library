<?php

namespace App\Services;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class OptionalUserResolver
{
    /**
     * 返回可选的用户对象（无中间件从 Bearer Token 解析），失败时返回 null。
     */
    public function user(Request $request): mixed
    {
        try {
            $u = $request->user();
            if ($u) return $u;
        } catch (\Throwable $e) {
            // ignore
        }
        $auth = (string)$request->header('Authorization', '');
        if ($auth && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            $plain = $m[1] ?? '';
            if ($plain !== '') {
                try {
                    $token = PersonalAccessToken::findToken($plain);
                    if ($token) return $token->tokenable;
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }
        return null;
    }

    /**
     * 返回用户 ID，未登录或无法解析时返回 0。
     */
    public function id(Request $request): int
    {
        $u = $this->user($request);
        if (!$u) return 0;
        return method_exists($u, 'getAuthIdentifier') ? (int)$u->getAuthIdentifier() : (int)($u->id ?? 0);
    }
}
