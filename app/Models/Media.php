<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class Media extends Model { use HasFactory, SoftDeletes; protected $table='media'; protected $guarded=['id']; protected function casts(): array{return ['is_primary'=>'boolean','variants'=>'array','metadata'=>'array'];} public function brand(){return $this->belongsTo(Brand::class);} public function mediable(){return $this->morphTo();} public function url(): string{return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);} }
