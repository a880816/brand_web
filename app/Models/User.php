<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLES = ['member', 'brand_admin', 'super_admin'];
    protected $fillable = ['name', 'email', 'email_verified_at', 'password', 'role', 'status', 'last_login_at'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['email_verified_at'=>'datetime','last_login_at'=>'datetime','password'=>'hashed']; }
    public function brands(): BelongsToMany { return $this->belongsToMany(Brand::class)->withTimestamps(); }
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function canManageBrand(Brand|int $brand): bool { $id=$brand instanceof Brand?$brand->id:$brand; return $this->isSuperAdmin() || ($this->role==='brand_admin' && $this->brands()->whereKey($id)->exists()); }
}
