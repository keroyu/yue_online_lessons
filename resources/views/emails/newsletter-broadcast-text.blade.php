{{ $post->title }}

{!! $bodyText !!}

在網站上閱讀這篇文章：
{{ $postUrl }}

---
你收到這封信是因為訂閱了《{{ \App\Models\SiteSetting::siteName() }}》電子報。
退訂（仍保留會員身分）：{{ $unsubscribeUrl }}
