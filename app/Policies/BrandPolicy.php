<?php
namespace App\Policies;
use App\Models\{Brand,User};
class BrandPolicy { public function view(User $user,Brand $brand):bool{return $user->canManageBrand($brand);} public function update(User $user,Brand $brand):bool{return $user->canManageBrand($brand);} public function manage(User $user):bool{return $user->isSuperAdmin();} }
