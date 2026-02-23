<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<p><strong>{{ $courseName }}</strong> — {{ $lessonTitle }}</p>

@if($hasVideo)
<p>▶▶ 本課程包含教學影片，請至網站觀看</p>
@endif

@if($htmlContent)
{!! $htmlContent !!}
@else
<p>新的課程內容已經解鎖了，請至網站觀看。</p>
@endif
</body>
</html>
