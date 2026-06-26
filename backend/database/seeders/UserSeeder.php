<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'trader@goldai.com'],
            [
                'name'     => 'Gold Trader',
                'password' => Hash::make('password123'),
                'tier'     => 'premium',
            ]
        );
    }
}