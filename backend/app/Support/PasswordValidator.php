<?php

namespace App\Support;

use App\Models\SystemSetting;

/**
 * 密码验证器
 */
class PasswordValidator
{
    /**
     * 验证密码是否符合系统要求
     *
     * @param string $password 待验证的密码
     * @return array{valid: bool, errors: array<string>}
     */
    public static function validate(string $password): array
    {
        $errors = [];
        
        // 获取最小长度要求
        $minLength = SystemSetting::value('security.password_min_length', 6);
        if (strlen($password) < $minLength) {
            $errors[] = "密码长度至少需要 {$minLength} 个字符";
        }
        
        // 检查是否需要强密码
        $requireStrong = SystemSetting::value('security.require_strong_password', false);
        if ($requireStrong) {
            $hasUppercase = preg_match('/[A-Z]/', $password);
            $hasLowercase = preg_match('/[a-z]/', $password);
            $hasNumber = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
            
            if (!$hasUppercase) {
                $errors[] = '密码必须包含至少一个大写字母';
            }
            if (!$hasLowercase) {
                $errors[] = '密码必须包含至少一个小写字母';
            }
            if (!$hasNumber) {
                $errors[] = '密码必须包含至少一个数字';
            }
            if (!$hasSpecial) {
                $errors[] = '密码必须包含至少一个特殊字符';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * 获取密码规则说明文本
     *
     * @return string
     */
    public static function getRulesDescription(): string
    {
        $minLength = SystemSetting::value('security.password_min_length', 6);
        $requireStrong = SystemSetting::value('security.require_strong_password', false);
        
        $rules = ["至少 {$minLength} 个字符"];
        
        if ($requireStrong) {
            $rules[] = '包含大小写字母';
            $rules[] = '包含数字';
            $rules[] = '包含特殊字符';
        }
        
        return implode('、', $rules);
    }
    
    /**
     * 获取 Laravel 验证规则数组
     *
     * @return array<string>
     */
    public static function getLaravelRules(): array
    {
        $minLength = SystemSetting::value('security.password_min_length', 6);
        $requireStrong = SystemSetting::value('security.require_strong_password', false);
        
        $rules = [
            'required',
            'string',
            "min:{$minLength}",
        ];
        
        if ($requireStrong) {
            // 使用自定义验证规则
            $rules[] = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/';
        }
        
        return $rules;
    }
}
