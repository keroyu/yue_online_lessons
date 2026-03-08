<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $courses = Course::where('is_published', true)->get(['id', 'updated_at']);

        return response()
            ->view('sitemap', ['courses' => $courses])
            ->header('Content-Type', 'application/xml');
    }
}
