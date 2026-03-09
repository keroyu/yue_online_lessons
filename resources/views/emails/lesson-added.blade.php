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

        <div style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 20px; margin-bottom: 24px; text-align: center;">
            <p style="color: #1d4ed8; font-size: 18px; font-weight: 600; margin: 0;">
                📢 課程新增內容通知
            </p>
        </div>

        <p style="color: #374151; font-size: 16px; line-height: 1.75; margin: 0 0 16px 0;">
            您購買的課程「<strong>{{ $course->name }}</strong>」剛新增了新內容：
        </p>

        <h2 style="color: #111827; font-size: 20px; font-weight: 600; margin: 0 0 24px 0;">
            「{{ $lesson->title }}」
        </h2>

        <p style="color: #374151; font-size: 16px; line-height: 1.75; margin: 0 0 24px 0;">
            記得回來觀看！更多精彩內容等著您。
        </p>

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ config('app.url') }}/member/classroom/{{ $course->id }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 32px; border-radius: 6px; font-weight: 600; font-size: 16px;">
                立即前往上課
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 32px 0;">

        <p style="color: #9ca3af; font-size: 12px; text-align: center; margin: 0;">
            &copy; {{ date('Y') }} 經營者時間銀行. All rights reserved.
        </p>
    </div>
</body>
</html>
