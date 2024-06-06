<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::create([
            'title' => 'Building Trust',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
            'teacher_id' => 2,
            'learning_path_id' => 1
        ]);

        Course::create([
            'title' => 'Building Need',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
            'teacher_id' => 2,
            'learning_path_id' => 1
        ]);

        Course::create([
            'title' => 'Handling Objection',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
            'teacher_id' => 2,
            'learning_path_id' => 1
        ]);

        Course::create([
            'title' => 'Trying to Close',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
            'teacher_id' => 2,
            'learning_path_id' => 1
        ]);

        Course::create([
            'title' => 'Closing',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
            'teacher_id' => 2,
            'learning_path_id' => 1
        ]);
    }
}
