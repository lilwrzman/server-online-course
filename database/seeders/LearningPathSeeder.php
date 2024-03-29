<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LearningPathSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LearningPath::create([
            'title' => 'Sales Marketing 01',
            'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Repudiandae sunt illo, saepe alias ipsam veniam porro reprehenderit quasi libero labore.'
        ]);

        LearningPath::create([
            'title' => 'Sales Marketing 02',
            'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Repudiandae sunt illo, saepe alias ipsam veniam porro reprehenderit quasi libero labore.'
        ]);

        LearningPath::create([
            'title' => 'Sales Marketing 03',
            'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Repudiandae sunt illo, saepe alias ipsam veniam porro reprehenderit quasi libero labore.'
        ]);
    }
}
