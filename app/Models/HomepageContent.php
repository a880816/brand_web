<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use Illuminate\Database\Eloquent\Model;

class HomepageContent extends Model
{
    use HasMedia;
    protected $guarded = ['id'];
    protected function casts(): array { return ['draft_data' => 'array', 'published_data' => 'array', 'published_at' => 'datetime']; }
    public function brand() { return $this->belongsTo(Brand::class); }
}
