{{ $post->title }}

@if($post->excerpt){{ $post->excerpt }}

@endif
在網站上閱讀全文：
{{ $postUrl }}

---
你收到這封信是因為訂閱了《{{ config('app.name', '經營者時間銀行') }}》電子報。
退訂（仍保留會員身分）：{{ $unsubscribeUrl }}
