<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageSection extends Model
{
    use HasFactory, HasMedia, SoftDeletes;

    public const TYPES = ['hero', 'text', 'image_text', 'services', 'courses', 'gallery', 'cta'];
    public const VARIANTS = ['default', 'split', 'centered', 'full_width'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function brand() { return $this->belongsTo(Brand::class); }
    public function page() { return $this->belongsTo(Page::class); }
}
