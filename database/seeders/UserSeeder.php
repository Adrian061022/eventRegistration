<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin felhasználó
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@events.hu',
            'password' => Hash::make('admin123'),
    	    'is_admin' => true,
        ]);

        // Teszt felhasználó
        User::factory()->create([
            'name' => 'Test',
            'email' => 'test@events.hu',
            'password' => Hash::make('test123'),
        ]);

        // További random felhasználók
        User::factory()->count(10)->create();
        
        $this->command->info('UserSeeder: 12 felhasználó létrehozva (1 admin, 1 test, 10 random).');
    }
}
