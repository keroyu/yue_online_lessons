@if($greetingName)Hi {{ $greetingName }}，

@endif
{{ $textContent ?: '新的內容已經解鎖了，請至網站觀看。' }}

— — — — — — — — — —
不想再收到這個商品的信件，可按此停止接收（Unsubscribe）：
{{ $unsubscribeUrl }}
