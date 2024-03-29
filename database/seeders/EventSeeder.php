<?php

namespace Database\Seeders;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'title' => 'Event 01',
            'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quae culpa at perferendis quasi ut rem?',
            'thumbnail' => '/events/event01.png',
            'quota' => 25,
            'type' => 'Offline',
            'status' => 'Upcoming',
            'info' => [
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->toDateString(),
                'speaker' => 'Person 01',
                'moderator' => 'Person 02'
            ]
        ]);
    }
}
