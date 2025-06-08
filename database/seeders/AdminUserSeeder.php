<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::firstOrCreate(
            [
                'phone_number' => '09158282780', // شماره تلفن ادمین
                'email' => 'ali.abdi@outlook.com',
            ],
            [
                'name' => 'علی عبدی',
                'password' => Hash::make('qwer1234@'), // رمز عبور را 'password' قرار می‌دهیم
                'is_admin' => true,
            ]
        );
    }
}
