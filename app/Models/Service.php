<?php
namespace App\Models;
use App\Models\Concerns\HasMedia; use App\Models\Concerns\Publishable; use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class Service extends Model { use HasFactory, HasMedia, Publishable, SoftDeletes; protected $guarded=['id']; protected function casts(): array{return ['published_at'=>'datetime'];} public function brand(){return $this->belongsTo(Brand::class);} }
