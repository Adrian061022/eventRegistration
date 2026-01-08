<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Registration;
use App\Models\User;
use App\Models\Event;

class RegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $events = Event::all();

        $sampleRegistrations = [
            [
                'user_id' => $users[1]->id,
                'event_id' => $events[0]->id,
                'status' => 'Elfogadva',
                'registered_at' => now()->subDays(5),
            ],
            [
                'user_id' => $users[1]->id,
                'event_id' => $events[0]->id,
                'status' => 'Függőben',
                'registered_at' => now()->subDays(3),
            ],
            [
                'user_id' => $users[2]->id,
                'event_id' => $events[1]->id,
                'status' => 'Elfogadva',
                'registered_at' => now()->subDays(7),
            ],
            [
                'user_id' => $users[3]->id,
                'event_id' => $events[2]->id,
                'status' => 'Elutasítva',
                'registered_at' => now()->subDays(10),
            ],
        ];

        foreach ($sampleRegistrations as $registration) {
            Registration::create($registration);
        }

        
        foreach ($users as $user) {
            $randomEvents = $events->random(rand(1, 3)); 

            foreach ($randomEvents as $event) {
                $exists = Registration::where('user_id', $user->id)
                    ->where('event_id', $event->id)
                    ->exists();

                if (!$exists) {
                    Registration::create([
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                        'status' => collect(['Függőben', 'Elfogadva', 'Elutasítva'])->random(),
                        'registered_at' => now()->subDays(rand(0, 15)),
                    ]);
                }
            }
        }

        $this->command->info("RegistrationSeeder: 4 fix és x véletlenjelentkezés létrehozva.");
    }
}