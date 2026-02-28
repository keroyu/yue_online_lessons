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
<p>⏰ 影片 {{ config('drip.video_access_hours') }} 小時內免費觀看，把握時間！</p>
@endif
@endif

@if($htmlContent)
{!! $htmlContent !!}
@else
<p>新的課程內容已經解鎖了，請至網站觀看。</p>
@endif

@if($promoTrackUrl)
<p style="text-align:center;margin:24px 0">
  <a href="{{ $promoTrackUrl }}"
     style="display:inline-block;background:#F0C14B;color:#373557;padding:12px 40px;border-radius:9999px;border:1px solid rgba(199,163,59,0.5);text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
    立即瞭解
  </a>
</p>
@endif

<p>前往教室觀看：{{ $classroomUrl }}</p>
<p>退訂：{{ $unsubscribeUrl }}</p>

{{-- Tracking pixel (hidden, records email open) --}}
@if($openPixelUrl)
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
@endif
</body>
</html>
