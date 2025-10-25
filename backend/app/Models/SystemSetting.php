<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'type', 'value'];

    // 返回 key => typed value 的关联数组
    public static function getAll(): array
    {
        $rows = self::all();
        $out = [];
        foreach ($rows as $r) {
            $k = $r->key;
            $type = $r->type;
            $val = $r->value;
            switch ($type) {
                case 'bool':
                    // 存储为 '1'/'0' 或 'true'/'false'
                    $parsed = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    // filter_var 可能返回 null 对于非布尔字符串，我们尽量回退为 false
                    if ($parsed === null) $parsed = ($val === '1' || strtolower($val) === 'true');
                    break;
                case 'int':
                    $parsed = is_numeric($val) ? intval($val) : 0;
                    break;
                case 'json':
                    $decoded = json_decode($val, true);
                    $parsed = $decoded === null ? ($val ? json_decode($val) : null) : $decoded;
                    break;
                default:
                    $parsed = $val;
            }
            $out[$k] = $parsed;
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

            if ($type === 'json') {
                $valueToStore = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif ($type === 'bool') {
                $valueToStore = $value ? '1' : '0';
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
}
