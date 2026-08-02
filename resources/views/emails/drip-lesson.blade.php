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

<p>不想再收到這個商品的信件，可<a href="{{ $unsubscribeUrl }}">按此停止接收</a>。</p>

{{-- Tracking pixel (hidden, records email open) --}}
@if($openPixelUrl)
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
@endif
</body>
</html>
