<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::query()->updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User', 'password' => Hash::make('password'), 'role' => 'user',
        ]);

        $ownerEmail = (string) env('SEED_OWNER_EMAIL', '');
        $ownerPassword = (string) env('SEED_OWNER_PASSWORD', '');
        if ($ownerEmail !== '' && $ownerPassword !== '') {
            User::query()->updateOrCreate(['email' => $ownerEmail], [
                'name' => 'Owner', 'password' => Hash::make($ownerPassword), 'role' => 'owner',
            ]);
        }
    }
}
