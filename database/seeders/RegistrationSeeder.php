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

        // Check if we have enough users and events
        if ($users->count() < 4 || $events->count() < 3) {
            $this->command->warn('RegistrationSeeder: Not enough users or events to create sample registrations.');
            return;
        }

        $sampleRegistrations = [
            [
                'user_id' => $users[1]->id,
                'event_id' => $events[0]->id,
                'status' => 'Elfogadva',
                'registered_at' => now()->subDays(5),
            ],
            [
                'user_id' => $users[2]->id,
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
            if ($events->count() === 0) {
                continue;
            }
            
            $randomCount = min(rand(1, 3), $events->count());
            $randomEvents = $events->random($randomCount); 

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