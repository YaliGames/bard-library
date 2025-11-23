<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'Bard Library')</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #f5f5f5;
      color: #333333;
      line-height: 1.6;
    }
    .email-wrapper {
      max-width: 600px;
      margin: 40px auto;
      background-color: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .email-header {
      background: #667eea;
      padding: 32px 24px;
      text-align: center;
    }
    .email-logo {
      font-size: 28px;
      font-weight: bold;
      color: #ffffff;
      margin: 0;
      letter-spacing: 1px;
    }
    .email-logo-icon {
      display: inline-block;
      margin-right: 8px;
      font-size: 32px;
    }
    .email-body {
      padding: 32px 24px;
    }
    .email-greeting {
      font-size: 18px;
      font-weight: 600;
      color: #333333;
      margin: 0 0 16px 0;
    }
    .email-content {
      font-size: 15px;
      color: #555555;
      margin: 16px 0;
    }
    .email-button {
      display: inline-block;
      padding: 14px 32px;
      margin: 24px 0;
      background: #667eea;
      color: #ffffff !important;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      font-size: 16px;
      transition: transform 0.2s;
    }
    .email-button:hover {
      transform: translateY(-2px);
    }
    .email-link {
      word-break: break-all;
      color: #667eea;
      font-size: 13px;
      display: block;
      margin: 16px 0;
      padding: 12px;
      background-color: #f8f9fa;
      border-radius: 4px;
      border-left: 3px solid #667eea;
    }
    .email-note {
      font-size: 13px;
      color: #888888;
      margin: 24px 0 16px 0;
      padding: 12px;
      background-color: #fff9e6;
      border-left: 3px solid #ffc107;
      border-radius: 4px;
    }
    .email-footer {
      padding: 24px;
      text-align: center;
      background-color: #f8f9fa;
      border-top: 1px solid #e9ecef;
    }
    .email-footer-text {
      font-size: 13px;
      color: #888888;
      margin: 8px 0;
    }
    .email-footer-brand {
      font-weight: 600;
      color: #667eea;
    }
    .email-divider {
      height: 1px;
      background-color: #e9ecef;
      margin: 24px 0;
    }
    @media only screen and (max-width: 600px) {
      .email-wrapper {
        margin: 0;
        border-radius: 0;
      }
      .email-body {
        padding: 24px 16px;
      }
    }
  </style>
</head>
<body>
  <div class="email-wrapper">
    <!-- Header -->
    <div class="email-header">
      <h1 class="email-logo">
        <span class="email-logo-icon">📚</span>
        {{ $systemName ?? 'Bard Library' }}
      </h1>
    </div>

    <!-- Body -->
    <div class="email-body">
      @yield('content')
    </div>

    <!-- Footer -->
    <div class="email-footer">
      <p class="email-footer-text">
        此邮件由 <span class="email-footer-brand">{{ $systemName ?? 'Bard Library' }}</span> 自动发送，请勿直接回复
      </p>
      <p class="email-footer-text">
        Bard Library 是一个开源电子书管理系统，您可以访问
        <a class="email-footer-brand" href="https://github.com/YaliGames/bard-library" target="_blank" rel="noopener">Github</a>
        了解更多信息。
      </p>
      <p class="email-footer-text">
        © {{ date('Y') }} {{ $systemName ?? 'Bard Library' }}. All rights reserved.
      </p>
    </div>
  </div>
</body>
</html>
