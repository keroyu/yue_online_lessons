您好，

您擁有的課程「{{ $course->name }}」新增了小節：
「{{ $lesson->title }}」

立即觀看新小節：
{{ $lessonUrl }}

{{ \App\Models\SiteSetting::siteName() }}
