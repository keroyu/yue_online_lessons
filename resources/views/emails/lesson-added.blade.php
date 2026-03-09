您好，

您擁有的{{ match($course->type) { 'lecture' => '講座', 'mini' => '迷你課', default => '課程' } }}
「{{ $course->name }}」
新增了小節：
「{{ $lesson->title }}」

歡迎回來繼續學習：
{{ config('app.url') }}/member/classroom/{{ $course->id }}

經營者時間銀行
