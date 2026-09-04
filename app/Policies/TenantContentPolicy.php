<?php
namespace App\Policies;
use App\Models\User; use Illuminate\Database\Eloquent\Model;
class TenantContentPolicy { public function before(User $user): ?bool{return $user->isSuperAdmin()?true:null;} public function viewAny(User $user): bool{return $user->role==='brand_admin';} public function view(User $user, Model $model): bool{return $user->canManageBrand($model->brand_id);} public function create(User $user): bool{return $user->role==='brand_admin';} public function update(User $user, Model $model): bool{return $this->view($user,$model);} public function delete(User $user, Model $model): bool{return $this->view($user,$model);} }
