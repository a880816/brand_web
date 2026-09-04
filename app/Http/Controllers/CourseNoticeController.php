<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\BrandContext;

class CourseNoticeController extends Controller
{
    public function __invoke(string $slug, BrandContext $context)
    {
        $course = Course::where('brand_id', $context->id())->published()->where('slug', $slug)->firstOrFail();
        abort_unless(filled($course->notion_url), 404);
        $host = strtolower((string) parse_url($course->notion_url, PHP_URL_HOST));
        abort_unless($host === 'notion.so' || $host === 'www.notion.so' || str_ends_with($host, '.notion.site'), 422, '行前通知網址必須使用 Notion。');
        return view('site.courses.notice', compact('course'));
    }
}
