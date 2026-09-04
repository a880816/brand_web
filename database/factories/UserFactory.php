<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return ['name'=>fake()->name(),'email'=>fake()->unique()->safeEmail(),'email_verified_at'=>now(),'password'=>Hash::make('TestingPassword!'),'role'=>'member','status'=>'active'];
    }

    public function brandAdmin(): static { return $this->state(['role'=>'brand_admin']); }
    public function superAdmin(): static { return $this->state(['role'=>'super_admin']); }
    public function disabled(): static { return $this->state(['status'=>'disabled']); }
}
