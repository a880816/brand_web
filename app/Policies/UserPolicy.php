<?php
namespace App\Policies;
use App\Models\User;
class UserPolicy { public function manage(User $user):bool{return $user->isSuperAdmin();} public function update(User $user,User $target):bool{return $user->isSuperAdmin() || $user->id===$target->id;} }
