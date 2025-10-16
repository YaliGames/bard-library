<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>重置密码</title>
</head>
<body>
  <p>您好，{{ $username }}：</p>
  <p>我们收到了您在 Bard Library 的密码重置请求。如果是您本人操作，请点击以下链接重置密码：</p>
  <p><a href="{{ $resetLink }}" target="_blank" rel="noopener">{{ $resetLink }}</a></p>
  <p>该链接在 24 小时内有效。如果这不是您的操作，请忽略此邮件。</p>
  <p>— Bard Library 团队</p>
</body>
</html>
