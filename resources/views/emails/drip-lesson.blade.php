<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<p><strong>{{ $courseName }}</strong> — {{ $lessonTitle }}</p>

@if($hasVideo)
<p>▶▶ 本課程包含教學影片，請至網站觀看</p>
@if(config('drip.video_access_hours'))
<p>▶ 影片 {{ config('drip.video_access_hours') }} 小時內免費觀看，把握時間！</p>
@endif
@endif

@if($htmlContent)
{!! $htmlContent !!}
@else
<p>新的課程內容已經解鎖了，點擊下方連結開始閱讀。</p>
@endif

<p>{{ $hasVideo ? '▶ 前往觀看' : '📖 到網站上閱讀' }}<br>
{{ $classroomUrl }}</p>

<p>---<br>
如不想繼續收到此系列通知，請點此退訂：<br>
{{ $unsubscribeUrl }}</p>
</body>
</html>
