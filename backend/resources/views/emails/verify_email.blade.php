@extends('emails.layout')

@section('title', '激活您的账户')

@section('content')
  <p class="email-greeting">您好，{{ $username }}！</p>
  
  <div class="email-content">
    <p>欢迎加入 <strong>{{ $systemName }}</strong>！</p>
    <p>为了确保账户安全，请点击下方按钮验证您的邮箱地址：</p>
  </div>

  <div style="text-align: center;">
    <a href="{{ $verificationUrl }}" class="email-button" target="_blank" rel="noopener">
      验证邮箱地址
    </a>
  </div>

  <div class="email-link">
    如果按钮无法点击，请复制以下链接到浏览器中打开：<br>
    {{ $verificationUrl }}
  </div>

  <div class="email-note">
    <strong>⏱️ 重要提示：</strong><br>
    • 该验证链接在 <strong>60 分钟</strong>内有效<br>
    • 验证成功后即可开始使用 {{ $systemName }} 的全部功能<br>
    • 如果这不是您注册的账户，请忽略此邮件
  </div>

  <div class="email-divider"></div>

  <div class="email-content" style="font-size: 14px; color: #666;">
    <p><strong>开始探索您的图书馆：</strong></p>
    <p>📖 管理您的电子书收藏<br>
       🏷️ 创建自定义标签和书架<br>
       📊 追踪阅读进度<br>
       🔍 强大的搜索和筛选功能</p>
  </div>
@endsection
