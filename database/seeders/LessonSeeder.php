<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::with('chapters')->get();

        $sampleVideos = [
            ['platform' => 'vimeo', 'id' => '1032766965', 'url' => 'https://vimeo.com/1032766965'],
            ['platform' => 'vimeo', 'id' => '824804225', 'url' => 'https://vimeo.com/824804225'],
            ['platform' => 'youtube', 'id' => 'dQw4w9WgXcQ', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ];

        $sampleHtmlContent = [
            '<h2>本單元學習目標</h2><p>在這個單元中，你將學習到以下重點：</p><ul><li>核心概念理解</li><li>實際操作技巧</li><li>常見問題解決</li></ul>',
            '<h2>補充教材</h2><p>以下是本單元的補充資料：</p><ol><li>延伸閱讀文章</li><li>練習題目</li><li>參考資源連結</li></ol>',
            '<h2>重點整理</h2><p>本章節的重點如下：</p><blockquote>學習的關鍵在於持續練習與反覆複習。</blockquote>',
        ];

        $lessonTitles = [
            '課程介紹與學習目標',
            '基本概念說明',
            '實作練習',
            '進階應用',
            '案例分析',
            '總結與回顧',
            '問答時間',
            '作業說明',
        ];

        foreach ($courses as $course) {
            $sortOrder = 0;

            // Create lessons for each chapter
            foreach ($course->chapters as $chapter) {
                $numLessons = rand(2, 4);

                for ($i = 0; $i < $numLessons; $i++) {
                    $useVideo = rand(0, 4) > 0; // 80% chance of having video

                    if ($useVideo) {
                        $video = $sampleVideos[array_rand($sampleVideos)];
                        Lesson::create([
                            'course_id' => $course->id,
                            'chapter_id' => $chapter->id,
                            'title' => $lessonTitles[array_rand($lessonTitles)],
                            'video_platform' => $video['platform'],
                            'video_id' => $video['id'],
                            'video_url' => $video['url'],
                            'duration_seconds' => rand(180, 1800), // 3-30 minutes
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        Lesson::create([
                            'course_id' => $course->id,
                            'chapter_id' => $chapter->id,
                            'title' => $lessonTitles[array_rand($lessonTitles)],
                            'html_content' => $sampleHtmlContent[array_rand($sampleHtmlContent)],
                            'duration_seconds' => rand(60, 300), // 1-5 minutes reading time
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
            }

            // Create 1-2 standalone lessons (no chapter)
            $numStandalone = rand(1, 2);
            for ($i = 0; $i < $numStandalone; $i++) {
                $video = $sampleVideos[array_rand($sampleVideos)];
                Lesson::create([
                    'course_id' => $course->id,
                    'chapter_id' => null,
                    'title' => '附錄：' . $lessonTitles[array_rand($lessonTitles)],
                    'video_platform' => $video['platform'],
                    'video_id' => $video['id'],
                    'video_url' => $video['url'],
                    'duration_seconds' => rand(180, 900),
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }
}
