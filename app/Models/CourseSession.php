<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSession extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime']; }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function plans() { return $this->hasMany(CoursePlan::class)->orderBy('sort_order'); }
    public function registrations() { return $this->hasMany(CourseRegistration::class); }
    public function effectivePlans() { return $this->plans->isNotEmpty() ? $this->plans : $this->course->plans; }
    public function reservedParticipants(): int { return (int) $this->registrations()->where('status', '!=', 'cancelled')->sum('participants'); }
    public function remainingCapacity(): int { return max(0, $this->capacity - $this->reservedParticipants()); }
    public function closesAt() { return $this->starts_at->copy()->subDays($this->registration_close_days ?? config('courses.registration_close_days')); }
    public function availabilityStatus(): string
    {
        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->status !== 'open' || now()->greaterThanOrEqualTo($this->closesAt())) return 'closed';
        return $this->remainingCapacity() > 0 ? 'open' : 'full';
    }
}
