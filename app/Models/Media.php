<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';
    protected $guarded = ['id'];
    protected function casts(): array { return ['is_primary'=>'boolean','variants'=>'array','metadata'=>'array']; }

    public function brand() { return $this->belongsTo(Brand::class); }
    public function mediable() { return $this->morphTo(); }

    public function url(?string $variant=null): string
    {
        return Storage::disk($this->disk)->url($variant && isset($this->variants[$variant]) ? $this->variants[$variant] : $this->path);
    }

    public function srcset(): ?string
    {
        if (! $this->variants) return null;
        $widths=['thumbnail'=>480,'card'=>960,'detail'=>1600];
        return collect($widths)->filter(fn($width,$name)=>isset($this->variants[$name]))->map(fn($width,$name)=>$this->url($name).' '.$width.'w')->join(', ');
    }
}
