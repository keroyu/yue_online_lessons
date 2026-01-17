<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Database\Seeder;

class ChapterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            $chapterTitles = [
                ['第一章：基礎入門', '第二章：進階技巧', '第三章：實戰應用'],
                ['課前準備', '核心概念', '案例分析'],
                ['入門篇', '應用篇', '進階篇'],
            ];

            $titles = $chapterTitles[array_rand($chapterTitles)];
            $numChapters = rand(2, 3);

            for ($i = 0; $i < $numChapters; $i++) {
                Chapter::create([
                    'course_id' => $course->id,
                    'title' => $titles[$i],
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
