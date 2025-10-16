<?php

// 修复在命名空间上下文中直接调用 mb_split 报 "Illuminate\Support\mb_split" 的问题。
// 当在命名空间中使用未导入的函数名时，可能被解析为当前命名空间下的函数。
// 这里提供一个全局函数包装，确保调用内置函数 \mb_split。

if (!function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array
    {
        if (!\function_exists('\\mb_split')) {
            throw new \RuntimeException('mbstring extension is required.');
        }
        // 注意：$limit 在某些 PHP 版本的 mb_split 中不可用，这里做兼容处理
        if ($limit > 0) {
            return \mb_split($pattern, $string, $limit);
        }
        return \mb_split($pattern, $string);
    }
}
