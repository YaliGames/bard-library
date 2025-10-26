<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string']
        ]);
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }
        if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
            // 未验证邮箱
            return response()->json(['message' => 'Email not verified'], 403);
        }
        $token = $user->createToken('web')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function register(Request $request)
    {
        if (!\App\Models\SystemSetting::value('permissions.allow_user_registration', true)) {
            return response()->json(['message' => 'Registration disabled'], 403);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->save();
        // 发送验证邮件
        event(new Registered($user));
        return response()->json(['success' => true]);
    }

    public function forgotPassword(Request $request)
    {
        if (!\App\Models\SystemSetting::value('permissions.allow_recover_password', true)) {
            return response()->json(['message' => 'Password recovery disabled'], 403);
        }
        $data = $request->validate([
            'email' => ['required', 'email']
        ]);
        // 静默处理，避免泄露邮箱是否存在
        $user = User::where('email', $data['email'])->first();
        if ($user) {
            // 生成一次性令牌（明文）并存储哈希，24 小时有效
            $plain = bin2hex(random_bytes(24));
            $hash = hash('sha256', $plain);
            // upsert: 同一邮箱仅保留最新一条
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => $hash, 'created_at' => now()]
            );
            // 发送邮件
            $frontendUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
            $resetLink = $frontendUrl . '/reset?email=' . urlencode($user->email) . '&token=' . urlencode($plain);
            try {
                Mail::to($user->email)->send(new ResetPasswordMail($resetLink, $user->name ?? $user->email));
            } catch (\Throwable $e) {
                // 邮件发送失败不暴露细节，仍返回 success
            }
            // 不返回 token
        }
        return response()->json(['success' => true]);
    }

    public function resetPassword(Request $request)
    {
        if (!\App\Models\SystemSetting::value('permissions.allow_recover_password', true)) {
            return response()->json(['message' => 'Password recovery disabled'], 403);
        }
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'token' => ['required', 'string']
        ]);
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        // 校验 token（存在、未过期、哈希匹配）
        $rec = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        if (!$rec) {
            return response()->json(['message' => 'Invalid or expired token'], 422);
        }
        // 24h 有效期
        if ($rec->created_at && now()->diffInSeconds(Carbon::parse($rec->created_at)) > 24 * 3600) {
            return response()->json(['message' => 'Token expired'], 422);
        }
        $hash = hash('sha256', (string)$data['token']);
        // 表 token 列储存hash
        if (!hash_equals(($rec->token ?? ''), $hash)) {
            return response()->json(['message' => 'Invalid token'], 422);
        }
        // 重置密码并删除 token
        $user->password = Hash::make($data['password']);
        $user->save();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        return response()->json(['success' => true]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }
        // 统一返回 JSON，交由 FormatJsonResponse 包裹为 { code:0, data:{success:true}, message:'' }
        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    public function resendVerification(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email']
        ]);
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            // 静默成功，避免暴露邮箱是否存在
            return response()->json(['success' => true]);
        }
        if (method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail()) {
            return response()->json(['success' => true]);
        }
        event(new Registered($user));
        return response()->json(['success' => true]);
    }

    // 公共验证链接（无需登录），用于处理邮件中的签名 URL
    public function verifyEmailPublic(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
        // 校验签名
        if (! URL::hasValidSignature($request)) {
            return response('Invalid or expired verification link.', 403);
        }
        // 校验 hash 与邮箱一致
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response('Invalid verification hash.', 400);
        }
        if ($user->hasVerifiedEmail()) {
            return redirect(config('app.url') . '/login?verified=1&status=already');
        }
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
        // 验证成功后跳转到前端登录页并带参数
        return redirect(config('app.url') . '/login?verified=1');
    }

    // ===== Profile APIs =====
    public function updateMe(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'website' => ['sometimes', 'nullable', 'string', 'max:200'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);
        $user->fill($data);
        $user->save();
        return $user;
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => '当前密码不正确'], 422);
        }
        $user->password = Hash::make($data['new_password']);
        $user->save();
        return ['success' => true];
    }

    public function requestDelete(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500']
        ]);
        $user->deletion_requested_at = now();
        $user->deletion_reason = $data['reason'] ?? null;
        $user->save();
        return ['success' => true];
    }

    // ===== User settings =====
    public function getSettings(Request $request)
    {
        $user = $request->user();
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['data' => []]
        );
        return $settings->data ?? [];
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'data' => ['required', 'array']
        ]);
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['data' => []]
        );
        $settings->data = $data['data'];
        $settings->save();
        return $settings->data;
    }
}
