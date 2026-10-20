<?php

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonKitCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_name_and_version_are_shared_with_every_page(): void
    {
        config(['lessonkit.version' => '9.8.7']);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('lessonkit.name', 'LessonKit')
            ->where('lessonkit.version', '9.8.7'));
    }
}
