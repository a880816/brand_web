<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrder extends Model
{
    use SoftDeletes;
    protected $guarded = ['id', 'reference', 'recipient_token_hash', 'paid_at', 'voided_at'];
    protected $hidden = ['recipient_token_hash'];
    protected function casts(): array { return ['shipping_details' => 'array', 'bank_snapshot' => 'array', 'subtotal' => 'decimal:2', 'shipping_fee' => 'decimal:2', 'total' => 'decimal:2', 'free_shipping' => 'boolean', 'recipient_link_expires_at' => 'datetime', 'paid_at' => 'datetime', 'voided_at' => 'datetime']; }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(SaleOrderItem::class); }
    public function recipientLinkIsValid(): bool { return filled($this->recipient_token_hash) && $this->recipient_link_expires_at?->isFuture() && ! in_array($this->status, ['paid', 'voided'], true); }
}
