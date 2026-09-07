<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, HasMedia, SoftDeletes;
    protected $guarded = ['id'];
    protected function casts(): array { return ['theme_settings'=>'array']; }
    public function homepageContent() { return $this->hasOne(HomepageContent::class); }
    public function courses() { return $this->hasMany(Course::class); }
    public function plantVarieties() { return $this->hasMany(PlantVariety::class); }
    public function materials() { return $this->hasMany(Material::class); }
    public function saleOrders() { return $this->hasMany(SaleOrder::class); }
    public function users() { return $this->belongsToMany(User::class)->withTimestamps(); }

    public function bankSnapshot(): array
    {
        return collect($this->only([
            'bank_name', 'bank_code', 'bank_branch', 'bank_account_name',
            'bank_account_number', 'remittance_notice',
        ]))->filter(fn ($value) => filled($value))->all();
    }
}
