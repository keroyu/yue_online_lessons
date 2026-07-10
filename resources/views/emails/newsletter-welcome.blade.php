<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>訂閱成功</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,'Noto Sans TC',Arial,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;padding:32px 24px;">
        <h1 style="font-size:20px;margin:0 0 16px;">訂閱成功，歡迎加入 🎉</h1>

        <p style="font-size:15px;line-height:1.7;margin:0 0 16px;">
            感謝你訂閱《{{ config('app.name', '經營者時間銀行') }}》電子報。<br>
            之後我們會不定期寄送實用的 Prompt、免費教學短片與輕量筆記給你。
        </p>

        <p style="font-size:15px;line-height:1.7;margin:0 0 24px;">
            你同時已成為網站會員，可用這個 Email 直接登入。
        </p>

        <p style="font-size:13px;color:#9ca3af;line-height:1.7;margin:24px 0 0;border-top:1px solid #e5e7eb;padding-top:16px;">
            不想再收到電子報？
            <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">按此退訂</a>（退訂後仍保留會員身分）。
        </p>
    </div>
</body>
</html>
