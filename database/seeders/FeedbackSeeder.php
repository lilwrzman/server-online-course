<?php

namespace Database\Seeders;

use App\Models\CourseFeedback;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CourseFeedback::create([
            'course_id' => 1,
            'user_id' => 2,
            'rating' => 4,
            'review' => 'Sangat Bagus!'
        ]);

        CourseFeedback::create([
            'course_id' => 1,
            'user_id' => 3,
            'rating' => 3,
            'review' => 'Sangat Bagus!'
        ]);
    }
}
