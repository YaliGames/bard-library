<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormatJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // 仅处理 API 路由且为 JSON 响应
        if (!$request->is('api/*')) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $status = $response->getStatusCode();
            $payload = $response->getData(true);
            if (is_array($payload) && array_key_exists('code', $payload) && array_key_exists('data', $payload)) {
                return $response; // 已包装，跳过
            }
            // 提取 message（兼容字符串/数组/空）
            $message = '';
            if (is_array($payload)) {
                $message = $payload['message'] ?? '';
            } elseif (is_string($payload)) {
                $message = $payload;
            }
            if ($status >= 400 && $message === '') {
                // 提供按状态码的兜底文案
                $message = match ($status) {
                    401 => 'Unauthenticated',
                    403 => 'Forbidden',
                    404 => 'Not Found',
                    default => 'Request failed',
                };
            }
            // 错误时 data 至少包含 message，避免前端拿不到信息
            $data = $payload;
            if ($status >= 400) {
                if (!is_array($payload)) {
                    $data = ['message' => $message ?: ''];
                } elseif (!array_key_exists('message', $payload)) {
                    $data = array_merge($payload, ['message' => $message]);
                }
            }
            $wrapped = [
                'code' => $status >= 400 ? $status : 0,
                'data' => $data,
                'message' => $message,
            ];
            $response->setData($wrapped);
            return $response;
        }

        return $response;
    }
}
