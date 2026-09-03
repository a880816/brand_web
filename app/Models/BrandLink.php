<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class BrandLink extends Model { use HasFactory; protected $guarded=['id']; protected static function booted(): void { static::saving(function(self $link){ if(!filter_var($link->url,FILTER_VALIDATE_URL)||!in_array(parse_url($link->url,PHP_URL_SCHEME),['http','https'],true)) throw new \InvalidArgumentException('External link must be a valid HTTP(S) URL.'); }); } public function brand(){return $this->belongsTo(Brand::class);} }
