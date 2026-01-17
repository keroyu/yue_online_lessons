<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'themustbig@gmail.com',
            'nickname' => '管理員',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'email' => 'member1@example.com',
            'nickname' => '學員小明',
            'role' => 'member',
            'email_verified_at' => now(),
        ]);

        User::create([
            'email' => 'member2@example.com',
            'nickname' => '學員小華',
            'role' => 'member',
            'email_verified_at' => now(),
        ]);

        User::create([
            'email' => 'member3@example.com',
            'nickname' => '學員小美',
            'role' => 'member',
            'email_verified_at' => now(),
        ]);
    }
}
