<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<p><strong>{{ $courseName }}</strong> — {{ $lessonTitle }}</p>

@if($hasVideo)
<p>▶▶ 本課程包含教學影片，請至網站觀看</p>
@if($videoAccessHours)
<p>⏰ 影片 {{ $videoAccessHours }} 小時內免費觀看，把握時間！</p>
@endif
@endif

@if($htmlContent)
{!! $htmlContent !!}
@else
<p>新的課程內容已經解鎖了，請至網站觀看。</p>
@endif

<p>前往教室觀看：{{ $classroomUrl }}</p>
<p>退訂：{{ $unsubscribeUrl }}</p>

{{-- Tracking pixel (hidden, records email open) --}}
@if($openPixelUrl)
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
@endif
</body>
</html>
