<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Console\Command;
use League\HTMLToMarkdown\HtmlConverter;

class ConvertHtmlToMarkdown extends Command
{
    protected $signature = 'content:html-to-markdown';

    protected $description = 'Convert existing HTML content in courses and lessons to Markdown';

    public function handle(): int
    {
        $converter = new HtmlConverter([
            'strip_tags' => false,
            'suppress_errors' => true,
        ]);

        // Convert courses.description_md
        $courses = Course::whereNotNull('description_md')->where('description_md', '!=', '')->get();
        $this->info("Converting {$courses->count()} courses...");

        foreach ($courses as $course) {
            $md = $converter->convert($course->description_md);
            $course->description_md = $md;
            $course->saveQuietly();
        }

        $this->info('Courses done.');

        // Convert lessons.md_content
        $lessons = Lesson::whereNotNull('md_content')->where('md_content', '!=', '')->get();
        $this->info("Converting {$lessons->count()} lessons...");

        foreach ($lessons as $lesson) {
            $md = $converter->convert($lesson->md_content);
            $lesson->md_content = $md;
            $lesson->saveQuietly();
        }

        $this->info('Lessons done.');
        $this->info('HTML → Markdown conversion complete.');

        return self::SUCCESS;
    }
}
