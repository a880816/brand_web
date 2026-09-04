<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantSoldUnit extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];
    protected function casts(): array { return ['price_snapshot' => 'decimal:2', 'sold_at' => 'datetime']; }
    public function variety() { return $this->belongsTo(PlantVariety::class, 'plant_variety_id'); }
    public function specimen() { return $this->belongsTo(PlantSpecimen::class, 'plant_specimen_id'); }
    public function orderItem() { return $this->belongsTo(SaleOrderItem::class, 'sale_order_item_id'); }
}
