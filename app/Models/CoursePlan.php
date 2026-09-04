<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePlan extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['price' => 'decimal:2', 'is_enabled' => 'boolean']; }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function session() { return $this->belongsTo(CourseSession::class, 'course_session_id'); }
}
