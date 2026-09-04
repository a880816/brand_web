<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantVariety extends Model
{
    use HasMedia, Publishable, SoftDeletes;
    protected $guarded = ['id'];
    protected function casts(): array { return ['published_at' => 'datetime']; }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function specimens() { return $this->hasMany(PlantSpecimen::class)->orderBy('sequence'); }
    public function availableStock(): int { return $this->specimens->where('status', 'published')->sum(fn (PlantSpecimen $item) => $item->availableQuantity()); }
}
