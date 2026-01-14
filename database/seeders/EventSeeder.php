<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;

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
                'max_participants' => 100,
            ],
            [
                'name' => 'Marketing Workshop',
                'description' => 'Gyakorlati marketing workshop digitális trendekkel.',
                'date' => now()->addDays(15),
                'location' => 'Online (Zoom)',
                'max_participants' => 50,
            ],
            [
                'name' => 'Webfejlesztés Alapjai',
                'description' => 'Kezdőknek szóló webfejlesztési tréning.',
                'date' => now()->subDays(10), // Múltbeli
                'location' => 'Debrecen, Egyetem',
                'max_participants' => 40,
            ],
        ];

        foreach ($events as $event) {
            \App\Models\Event::create($event);
        }

        Event::factory()->count(10)->create();
        $this->command->info("EventSeeder: 13 esemény létrehozva (3 fix + 10 random).");
    }
}
