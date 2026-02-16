<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: 'Noto Sans TC', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="color: #111827; font-size: 24px; font-weight: 600; margin: 0 0 24px 0; text-align: center;">
            經營者時間銀行
        </h1>

        <div style="background-color: #eef2ff; border: 1px solid #a5b4fc; border-radius: 8px; padding: 20px; margin-bottom: 24px; text-align: center;">
            <p style="color: #3730a3; font-size: 14px; font-weight: 500; margin: 0 0 4px 0;">
                {{ $courseName }}
            </p>
            <p style="color: #1e1b4b; font-size: 18px; font-weight: 600; margin: 0;">
                {{ $lessonTitle }}
            </p>
        </div>

        @if($hasVideo)
            <div style="background-color: #fefce8; border: 1px solid #fde047; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <p style="color: #854d0e; font-size: 14px; margin: 0; text-align: center;">
                    本課程包含教學影片，請至網站觀看
                </p>
            </div>
        @endif

        @if($htmlContent)
            <div style="color: #374151; font-size: 16px; line-height: 1.75; margin: 0 0 24px 0;">
                {!! $htmlContent !!}
            </div>
        @else
            <p style="color: #374151; font-size: 16px; line-height: 1.75; margin: 0 0 24px 0;">
                新的課程內容已為您解鎖，點擊按鈕開始閱讀。
            </p>
        @endif

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ $classroomUrl }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 32px; border-radius: 6px; font-weight: 600; font-size: 16px;">
                {{ $hasVideo ? '前往觀看' : '開始閱讀' }}
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 32px 0;">

        <p style="color: #9ca3af; font-size: 12px; text-align: center; margin: 0;">
            &copy; {{ date('Y') }} 經營者時間銀行. All rights reserved.
            <br>
            <a href="{{ $unsubscribeUrl }}" style="color: #9ca3af; text-decoration: underline;">退訂此系列</a>
        </p>
    </div>
</body>
</html>
