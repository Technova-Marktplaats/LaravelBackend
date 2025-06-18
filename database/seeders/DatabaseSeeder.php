<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Item;
use App\Models\ItemImage;
use App\Models\Reservation;
use App\Models\Category;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Leeg eerst de tabellen om conflicten te voorkomen
        $this->command->info('Database tabellen legen...');
        
        Reservation::truncate();
        ItemImage::truncate();
        Item::truncate();
        Category::truncate(); // Categorieën ook legen
        User::where('email', '!=', 'test@example.com')->delete(); // Behoud test gebruiker als deze al bestaat

        // Seed categorieën eerst (vereist voor items)
        $this->command->info('Categorieën seeden...');
        $this->call(CategorySeeder::class);

        // Maak eerst een aantal gebruikers aan
        $users = User::factory(10)->create();

        // Maak of vind de test gebruiker (voorkomt duplicate error)
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test Gebruiker',
                'password' => bcrypt('pass123'),
            ]
        );

        // Voeg test gebruiker toe aan de collectie voor gebruik in relaties
        $allUsers = $users->push($testUser);

        // Maak items aan met bestaande gebruikers
        $items = Item::factory(10)
            ->recycle($allUsers) // Hergebruik alle gebruikers inclusief test gebruiker
            ->create();

        // Voeg afbeeldingen toe aan items (1-3 afbeeldingen per item)
        $items->each(function ($item) {
            ItemImage::factory()
                ->count(rand(1, 3))
                ->for($item)
                ->create();
        });

        // Maak reserveringen aan voor beschikbare items
        $availableItems = $items->where('available', true)->take(15);
        $availableItems->each(function ($item) use ($allUsers) {
            // Niet alle items hebben reserveringen
            if (rand(1, 100) <= 60) { // 60% kans op reservering
                Reservation::factory()
                    ->for($item)
                    ->for($allUsers->random(), 'borrower')
                    ->create();
            }
        });

        // Maak wat extra bevestigde reserveringen
        Reservation::factory(5)
            ->confirmed()
            ->recycle($items)
            ->recycle($allUsers)
            ->create();

        $this->command->info('Database seeding voltooid!');
        $this->command->info('- ' . Category::count() . ' categorieën aangemaakt');
        $this->command->info('- ' . User::count() . ' gebruikers aangemaakt');
        $this->command->info('- ' . Item::count() . ' items aangemaakt');
        $this->command->info('- ' . ItemImage::count() . ' afbeeldingen aangemaakt');
        $this->command->info('- ' . Reservation::count() . ' reserveringen aangemaakt');
    }
}
