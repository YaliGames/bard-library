@extends('emails.layout')

@section('title', '图书分享')

@section('content')
  <p class="email-greeting">您好！</p>
  
  <div class="email-content">
    <p>{{ $userName }}向您分享了一本图书：</p>
  </div>

  <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;">
    <div style="font-size: 20px; font-weight: bold; color: #667eea; margin-bottom: 10px;">
      {{ $bookTitle }}
    </div>
    <p style="margin: 0; color: #6c757d;">文件已作为附件发送，请查收。</p>
  </div>

  <div class="email-content">
    <p>请在邮件附件中下载图书文件。如果您使用 Kindle 等电子书阅读器，可以直接将附件发送到设备邮箱。</p>
    <p>部分邮箱客户端可能会对大附件进行压缩。</p>
  </div>
@endsection
