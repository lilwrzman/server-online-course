<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'password' => 'Admin123',
            'role' => 'Superadmin',
            'status' => 'Active'
        ]);

        User::create([
            'email' => 'teacher01@gmail.com',
            'username' => 'teacher01',
            'password' => 'User1234',
            'role' => 'Teacher',
            'status' => 'Active',
            'info' => [
                'fullname' => 'Yunizel Bach',
                'bio' => "",
                'social_media' => [
                    [
                        'type' => 'Facebook',
                        'username' => 'Yunizel Bach',
                        'url' => 'https://facebook.com/yunizel_bach'
                    ], [
                        'type' => 'Instagram',
                        'username' => 'yunizelbach',
                        'url' => 'https://instagram.com/yunizelbach'
                    ]
                ]
            ]
        ]);

        User::create([
            'email' => 'teacher02@gmail.com',
            'username' => 'teacher02',
            'password' => 'User1234',
            'role' => 'Teacher',
            'status' => 'Active',
            'info' => [
                'fullname' => 'Yusriadi',
                'bio' => "",
                'social_media' => [
                    [
                        'type' => 'Facebook',
                        'username' => 'Yusriadi',
                        'url' => 'https://facebook.com/yusriadi'
                    ], [
                        'type' => 'Instagram',
                        'username' => 'yusriadi',
                        'url' => 'https://instagram.com/yusriadi'
                    ]
                ]
            ]
        ]);
    }
}
