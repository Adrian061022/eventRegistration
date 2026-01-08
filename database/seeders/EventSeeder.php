<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'name' => 'Tech Conference 2024',
                'description' => 'Éves technológiai konferencia innovatív témákkal.',
                'date' => now()->addDays(30),
                'location' => 'Budapest, BME Q épület',
                'max_attendees' => 100,
            ],
            [
                'name' => 'Marketing Workshop',
                'description' => 'Gyakorlati marketing workshop digitális trendekkel.',
                'date' => now()->addDays(15),
                'location' => 'Online (Zoom)',
                'max_attendees' => 50,
            ],
            [
                'name' => 'Webfejlesztés Alapjai',
                'description' => 'Kezdőknek szóló webfejlesztési tréning.',
                'date' => now()->subDays(10), // Múltbeli
                'location' => 'Debrecen, Egyetem',
                'max_attendees' => 40,
            ],
        ];
    }
}
