<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleOrderItem extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'snapshot' => 'array']; }
    public function order() { return $this->belongsTo(SaleOrder::class, 'sale_order_id'); }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function soldUnits() { return $this->hasMany(PlantSoldUnit::class); }
}
