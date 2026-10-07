<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,'Noto Sans TC',Arial,sans-serif;color:#1f2937;">
    {{-- First in the body: Gmail clips long mail and a trailing pixel goes with it (012 FR-035).
         Welcome mail has no broadcast to hang an open event on (012 D15). --}}
    @if($openPixelUrl)
    <img src="{{ $openPixelUrl }}" alt="" width="1" height="1" style="display:none;border:0;">
    @endif

    <div style="max-width:560px;margin:0 auto;padding:32px 24px;">
        <h1 style="font-size:22px;line-height:1.4;margin:0 0 16px;">{{ $post->title }}</h1>

        @if($post->cover_url)
        <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" style="width:100%;max-width:512px;border:0;display:block;margin:0 0 20px;">
        @endif

        {{-- Full post, rendered and sanitised by PostService::toEmailHtml() (012 US10) --}}
        <div style="font-size:15px;line-height:1.8;color:#374151;">
            {!! $bodyHtml !!}
        </div>

        <p style="font-size:13px;margin:28px 0 0;">
            <a href="{{ $postUrl }}" style="color:#0d9488;">在網站上閱讀這篇文章</a>
        </p>

        <p style="font-size:13px;color:#9ca3af;line-height:1.7;margin:24px 0 0;border-top:1px solid #e5e7eb;padding-top:16px;">
            你收到這封信是因為訂閱了《{{ \App\Models\SiteSetting::siteName() }}》電子報。
            <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">按此退訂</a>（退訂後仍保留會員身分）。
        </p>
    </div>
</body>
</html>
