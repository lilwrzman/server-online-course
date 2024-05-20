<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'teacher01@gmail.com',
            'username' => 'teacher01',
            'password' => 'teacher123',
            'role' => 'Teacher',
            'status' => 'Active',
            'info' => ['fullname' => 'Yunizel']
        ]);

        User::create([
            'email' => 'teacher02@gmail.com',
            'username' => 'teacher02',
            'password' => 'teacher123',
            'role' => 'Teacher',
            'status' => 'Active',
            'info' => ['fullname' => 'Yufrizal']
        ]);
    }
}
