<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: 'Noto Sans TC', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
    <div style="max-width: 520px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="color: #111827; font-size: 24px; font-weight: 600; margin: 0 0 24px 0; text-align: center;">
            經營者時間銀行
        </h1>

        <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 16px 0;">
            {{ $userName }} 您好，
        </p>

        <p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
            我們已收到您的「{{ $courseName }}」1v1 諮詢申請。<br>
            您選擇的時段目前為您暫時保留：
        </p>

        <div style="background-color: #f3f4f6; border-radius: 8px; padding: 20px 24px; text-align: center; margin: 0 0 24px 0;">
            <p style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">{{ $slotLabel }}</p>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">{{ $minutes }} 分鐘</p>
        </div>

        <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 0 0 24px 0;">
            <p style="color: #991b1b; font-size: 15px; font-weight: 700; line-height: 1.6; margin: 0;">
                預約尚未成立。
            </p>
            <p style="color: #b91c1c; font-size: 14px; line-height: 1.6; margin: 6px 0 0 0;">
                請點擊下方按鈕完成確認，時段才會正式為您保留。
            </p>
        </div>

        <div style="text-align: center; margin: 0 0 24px 0;">
            <a href="{{ $confirmUrl }}"
               style="display: inline-block; background-color: #0f766e; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; padding: 14px 40px; border-radius: 8px;">
                確認我的預約
            </a>
        </div>

        <p style="color: #374151; font-size: 14px; line-height: 1.6; margin: 0 0 24px 0;">
            此連結將於 <strong>{{ $expiresLabel }}</strong> 失效。逾時未確認，這個時段會自動釋出給其他人，您需要重新提出申請。
        </p>

        <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0 0 8px 0;">
            若按鈕無法點擊，請複製以下網址到瀏覽器開啟：
        </p>
        <p style="color: #0f766e; font-size: 13px; line-height: 1.6; word-break: break-all; margin: 0;">
            {{ $confirmUrl }}
        </p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 32px 0;">

        <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0 0 12px 0;">
            如果這不是您本人提出的申請，忽略這封信即可，時段會自動釋出。
        </p>

        <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0;">
            有任何問題請勿直接回覆此信，請寄信到
            <a href="mailto:{{ $supportEmail }}" style="color: #0f766e;">{{ $supportEmail }}</a>
            詢問。
        </p>

        <p style="color: #9ca3af; font-size: 12px; text-align: center; margin: 32px 0 0 0;">
            &copy; {{ date('Y') }} 經營者時間銀行. All rights reserved.
        </p>
    </div>
</body>
</html>
