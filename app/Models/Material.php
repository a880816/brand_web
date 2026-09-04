<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasMedia, Publishable, SoftDeletes;
    protected $guarded = ['id', 'reserved_quantity'];
    protected function casts(): array { return ['price' => 'decimal:2', 'specifications' => 'array', 'published_at' => 'datetime']; }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function availableQuantity(): int { return max(0, $this->stock_on_hand - $this->reserved_quantity); }
}
