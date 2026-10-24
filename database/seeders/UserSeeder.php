<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Carmen',
            'password' => Hash::make('87654321'),
            'role_id' => 1,
            'phone' => '5564173148',
        ]);
    }
}
