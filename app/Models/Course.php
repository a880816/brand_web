<?php
namespace App\Models;
use App\Models\Concerns\HasMedia; use App\Models\Concerns\Publishable; use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class Course extends Model { use HasFactory, HasMedia, Publishable, SoftDeletes; protected $guarded=['id']; protected function casts(): array{return ['published_at'=>'datetime','price_amount'=>'decimal:2'];} public function brand(){return $this->belongsTo(Brand::class);} }
