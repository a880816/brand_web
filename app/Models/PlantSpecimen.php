<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantSpecimen extends Model
{
    use HasMedia, SoftDeletes;
    protected $guarded = ['id', 'sequence', 'full_tag_name', 'reserved_quantity', 'sold_sequence'];
    protected function casts(): array { return ['price' => 'decimal:2', 'specifications' => 'array', 'published_at' => 'datetime']; }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function variety() { return $this->belongsTo(PlantVariety::class, 'plant_variety_id'); }
    public function availableQuantity(): int { return max(0, $this->stock_on_hand - $this->reserved_quantity); }
}
