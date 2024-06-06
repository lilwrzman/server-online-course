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
            'title' => 'Sales Marketing',
            'description' => 'Test 123...'
        ]);
    }
}
