<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\BrandContext;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class CourseNoticeQrController extends Controller
{
    public function __invoke(string $slug, BrandContext $context)
    {
        $course = Course::where('brand_id', $context->id())->published()
            ->whereNotNull('notion_url')->where('slug', $slug)->firstOrFail();
        $svg = (new SvgWriter)->write(new QrCode(route('course-notice.show', $course->slug), size: 280, margin: 12))->getString();
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
