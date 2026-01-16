<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: 'Noto Sans TC', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="color: #111827; font-size: 24px; font-weight: 600; margin: 0 0 24px 0; text-align: center;">
            經營者時間銀行
        </h1>

        <p style="color: #374151; font-size: 16px; line-height: 1.5; margin: 0 0 24px 0;">
            您好，
        </p>

        <p style="color: #374151; font-size: 16px; line-height: 1.5; margin: 0 0 24px 0;">
            您的登入驗證碼是：
        </p>

        <div style="background-color: #f3f4f6; border-radius: 8px; padding: 24px; text-align: center; margin: 0 0 24px 0;">
            <span style="font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #4f46e5;">
                {{ $code }}
            </span>
        </div>

        <p style="color: #6b7280; font-size: 14px; line-height: 1.5; margin: 0 0 16px 0;">
            此驗證碼將在 10 分鐘後失效。
        </p>

        <p style="color: #6b7280; font-size: 14px; line-height: 1.5; margin: 0;">
            如果您沒有嘗試登入，請忽略此郵件。
        </p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 32px 0;">

        <p style="color: #9ca3af; font-size: 12px; text-align: center; margin: 0;">
            &copy; {{ date('Y') }} 經營者時間銀行. All rights reserved.
        </p>
    </div>
</body>
</html>
