<?php

namespace Database\Seeders;

use App\Models\{Brand, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_ADMIN_PASSWORD');
        if (! $password) {
            $this->command?->warn('DEMO_ADMIN_PASSWORD 未設定，略過展示帳號。');
            return;
        }
        $accounts = [
            ['name'=>'總管理者','email'=>'super_admin@example.test','role'=>'super_admin'],
            ['name'=>'品牌 A 管理者','email'=>'brand_admin_a@example.test','role'=>'brand_admin','brand'=>'verdant'],
            ['name'=>'品牌 B 管理者','email'=>'brand_admin_b@example.test','role'=>'brand_admin','brand'=>'terracotta'],
        ];
        foreach ($accounts as $account) {
            $user = User::updateOrCreate(['email'=>$account['email']], ['name'=>$account['name'],'password'=>Hash::make($password),'role'=>$account['role'],'status'=>'active','email_verified_at'=>now()]);
            $brand = isset($account['brand']) ? Brand::where('slug',$account['brand'])->first() : null;
            $user->brands()->sync($brand ? [$brand->id] : []);
        }
    }
}
