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
    public function primaryMedia(string $collection): ?Media
    {
        $media = $this->mediaFor($collection)->orderByDesc('is_primary')->first();
        if ($media || $collection !== 'mother') return $media;

        $specimen = $this->specimens()->where('status', 'published')
            ->whereColumn('stock_on_hand', '>', 'reserved_quantity')->with('media')->first();
        return $specimen?->primaryMedia('specimen');
    }
}
