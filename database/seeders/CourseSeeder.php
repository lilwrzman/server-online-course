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
            'title' => 'Building Trust 01',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
        ]);

        Course::create([
            'title' => 'Building Trust 02',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
        ]);

        Course::create([
            'title' => 'Building Trust 03',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Labore, eveniet ea. Voluptatum dolorem assumenda officiis consectetur? Minus molestiae consequuntur magni!',
            'price' => 120000,
        ]);
    }
}
