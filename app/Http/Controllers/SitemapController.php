<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $courses = Course::where('is_published', true)->get(['id', 'slug', 'updated_at']);

        $posts = Post::published()->get(['slug', 'updated_at']);

        // Only tags that have at least one published post.
        $tags = Tag::whereHas('posts', fn ($q) => $q->published())->get(['slug']);

        return response()
            ->view('sitemap', compact('courses', 'posts', 'tags'))
            ->header('Content-Type', 'application/xml');
    }
}
