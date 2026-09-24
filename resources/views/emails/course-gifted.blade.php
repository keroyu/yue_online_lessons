您好，

您已獲得課程「{{ $courseName }}」的學習權限。@if($courseDescription)

{{ $courseDescription }}@endif

請登入帳號後，至「我的課程」查看：
{{ config('app.url') }}/member/learning

{{ \App\Models\SiteSetting::siteName() }}
