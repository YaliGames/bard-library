<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'type', 'value'];

    // 将 size 字符串（如 100MB, 1.5GB）解析为字节数
    public static function parseSizeToBytes($value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int)$value;
        $v = trim((string)$value);
        if (preg_match('/^([\d.]+)\s*(B|KB|MB|GB|TB)?$/i', $v, $m)) {
            $num = (float)$m[1];
            $unit = strtoupper($m[2] ?? 'B');
            $factor = 1;
            switch ($unit) {
                case 'KB': $factor = 1024; break;
                case 'MB': $factor = 1024 ** 2; break;
                case 'GB': $factor = 1024 ** 3; break;
                case 'TB': $factor = 1024 ** 4; break;
                default: $factor = 1; // B
            }
            return (int) round($num * $factor);
        }
        return null;
    }

    // 将字节数格式化为可读字符串（默认 MB 精度）
    public static function formatBytes($bytes): string
    {
        $bytes = (int)$bytes;
        $units = ['B','KB','MB','GB','TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return sprintf($i === 0 ? '%d%s' : '%.0f%s', $bytes, $units[$i]);
    }

    // 读取后端定义的 schema（config/settings.php）
    public static function schema(): array
    {
        return config('settings', []);
    }

    // 返回 key => typed value 的关联数组（若 DB 缺失则使用默认值）
    public static function getAll(): array
    {
        $rows = self::all()->keyBy('key');
        $schema = self::schema();
        $out = [];
        foreach ($schema as $k => $def) {
            $type = $def['type'] ?? 'string';
            if (isset($rows[$k])) {
                $r = $rows[$k];
                $type = $r->type ?: $type;
                $val = $r->value;
            } else {
                $val = $def['default'] ?? null;
            }
            $out[$k] = self::castValue($type, $val);
        }
        // 也包含 DB 中存在但 schema 未定义的项（向下兼容）
        foreach ($rows as $k => $r) {
            if (!array_key_exists($k, $out)) {
                $out[$k] = self::castValue($r->type, $r->value);
            }
        }
        return $out;
    }

    /**
     * 更新多个配置项。
     * 接受的 $data 可以是关联数组 key=>value（会根据 PHP 类型推断 type），
     * 或 key => ['type'=>..., 'value'=>...] 的显式形式。
     */
    public static function updateAll(array $data): array
    {
        $schema = self::schema();
        foreach ($data as $key => $v) {
            $explicit = false;
            $type = 'string';
            $valueToStore = null;

            if (is_array($v) && array_key_exists('type', $v) && array_key_exists('value', $v)) {
                $explicit = true;
                $type = $v['type'] ?? 'string';
                $value = $v['value'];
            } else {
                $value = $v;
                if (is_bool($value)) $type = 'bool';
                else if (is_int($value)) $type = 'int';
                else if (is_array($value) || is_object($value)) $type = 'json';
                else $type = 'string';
            }

            // 若 schema 中声明了类型，则以 schema 为准
            if (isset($schema[$key]['type'])) {
                $type = $schema[$key]['type'];
            }

            if ($type === 'json') {
                $valueToStore = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif ($type === 'bool') {
                $valueToStore = $value ? '1' : '0';
            } elseif ($type === 'int') {
                $valueToStore = (string) intval($value);
            } elseif ($type === 'size') {
                $bytes = self::parseSizeToBytes($value);
                if ($bytes === null) $bytes = self::parseSizeToBytes($schema[$key]['default'] ?? '0');
                $valueToStore = (string) ($bytes ?? 0);
            } else {
                $valueToStore = (string) $value;
            }

            self::updateOrCreate(
                ['key' => $key],
                ['type' => $type, 'value' => $valueToStore]
            );
        }

        return self::getAll();
    }

    // 将字符串值按类型转换为 PHP 值
    protected static function castValue(string $type, $val)
    {
        switch ($type) {
            case 'bool':
                $parsed = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($parsed === null) $parsed = ($val === '1' || strtolower((string)$val) === 'true');
                return (bool)$parsed;
            case 'int':
                return is_numeric($val) ? intval($val) : 0;
            case 'size':
                // 以字节数返回整数
                $bytes = self::parseSizeToBytes($val);
                return $bytes ?? 0;
            case 'json':
                $decoded = json_decode($val, true);
                return $decoded === null ? ($val ? json_decode($val) : null) : $decoded;
            default:
                return $val;
        }
    }

    // 获取单个配置值，带默认回退
    public static function value(string $key, $default = null)
    {
        $all = self::getAll();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }
}
