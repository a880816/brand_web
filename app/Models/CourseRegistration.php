<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseRegistration extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'payment_due_at' => 'datetime', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime', 'bank_snapshot' => 'array']; }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function session() { return $this->belongsTo(CourseSession::class, 'course_session_id'); }
    public function plan() { return $this->belongsTo(CoursePlan::class, 'course_plan_id'); }
    public function displayStatus(): string { return $this->status === 'awaiting_payment' && now()->greaterThan($this->payment_due_at) ? 'overdue' : $this->status; }
}
