<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,'Noto Sans TC',Arial,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;padding:32px 24px;">
        <h1 style="font-size:22px;line-height:1.4;margin:0 0 16px;">{{ $post->title }}</h1>

        @if($post->excerpt)
        <p style="font-size:15px;line-height:1.7;margin:0 0 20px;color:#374151;">{{ $post->excerpt }}</p>
        @endif

        @if($videoThumbUrl)
        <a href="{{ $postUrl }}" style="display:block;margin:0 0 20px;">
            <img src="{{ $videoThumbUrl }}" alt="觀看影片" style="width:100%;max-width:512px;border:0;display:block;">
        </a>
        @elseif($post->cover_url)
        <a href="{{ $postUrl }}" style="display:block;margin:0 0 20px;">
            <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" style="width:100%;max-width:512px;border:0;display:block;">
        </a>
        @endif

        <p style="margin:0 0 28px;">
            <a href="{{ $postUrl }}" style="display:inline-block;background:#0d9488;color:#ffffff;text-decoration:none;padding:12px 24px;font-weight:600;font-size:15px;">
                在網站上閱讀全文 →
            </a>
        </p>

        <p style="font-size:13px;color:#9ca3af;line-height:1.7;margin:24px 0 0;border-top:1px solid #e5e7eb;padding-top:16px;">
            你收到這封信是因為訂閱了《{{ config('app.name', '經營者時間銀行') }}》電子報。
            <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">按此退訂</a>（退訂後仍保留會員身分）。
        </p>
    </div>

    <img src="{{ $openPixelUrl }}" alt="" width="1" height="1" style="display:none;border:0;">
</body>
</html>
