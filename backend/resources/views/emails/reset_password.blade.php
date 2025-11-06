@extends('emails.layout')

@section('title', '重置您的密码')

@section('content')
  <p class="email-greeting">您好，{{ $username }}！</p>
  
  <div class="email-content">
    <p>我们收到了您在 <strong>Bard Library</strong> 的密码重置请求。</p>
    <p>如果是您本人操作，请点击下方按钮重置密码：</p>
  </div>

  <div style="text-align: center;">
    <a href="{{ $resetLink }}" class="email-button" target="_blank" rel="noopener">
      重置密码
    </a>
  </div>

  <div class="email-link">
    如果按钮无法点击，请复制以下链接到浏览器中打开：<br>
    {{ $resetLink }}
  </div>

  <div class="email-note">
    <strong>⏱️ 重要提示：</strong><br>
    • 该链接在 <strong>24 小时</strong>内有效<br>
    • 如果这不是您的操作，请忽略此邮件，您的密码将保持不变
  </div>
@endsection
