<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Paragraph rhythm for the body copy. content_md reaches the blade with
         every style and class attribute stripped (DripService::stripStylesForEmail),
         so this is the only place the letter's spacing can come from. Clients
         that drop <style> fall back to the default 1em margin, which is close. --}}
    <style>
        p { margin: 0 0 20px; }
    </style>
</head>
<body style="margin:0;padding:0;">
{{-- Gmail rewrites <body> into a div and drops its attributes, so the type
     scale has to live on a wrapper the client will keep. Deliberately no card,
     border, background or brand header: the copy is written as a personal
     letter, and a newsletter frame is the wrong signal for a mail that already
     sits one step from the Promotions tab (see 010 spec, 2026-08-02). --}}
<div style="font-family:-apple-system,'Noto Sans TC',Arial,sans-serif;font-size:16px;line-height:1.75;color:#1f2937;">
@if($greetingName)
<p>Hi {{ $greetingName }}，</p>
@endif
@if($htmlContent)
{!! $htmlContent !!}
@else
<p>新的內容已經解鎖了，請至網站觀看。</p>
@endif

{{-- Footer: pushed well clear of the body and visually quiet, so it reads as
     boilerplate rather than as the closing line of the letter --}}
<p>&nbsp;</p>
<p>&nbsp;</p>
<p style="margin:0;color:#c8c8c8;font-size:12px;letter-spacing:0.15em;">— — — — — — — — — —</p>
<p style="margin:8px 0 0;color:#9a9a9a;font-size:12px;line-height:1.7;">
    不想再收到這個商品的信件，可<a href="{{ $unsubscribeUrl }}" style="color:#9a9a9a;">按此停止接收（Unsubscribe）</a>。
</p>
</div>

{{-- Tracking pixel (hidden, records email open) --}}
@if($openPixelUrl)
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
@endif
</body>
</html>
