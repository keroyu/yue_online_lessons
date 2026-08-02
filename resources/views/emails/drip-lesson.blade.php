<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
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

{{-- Tracking pixel (hidden, records email open) --}}
@if($openPixelUrl)
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
@endif
</body>
</html>
