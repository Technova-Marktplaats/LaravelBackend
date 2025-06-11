<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Elektronica',
                'slug' => 'elektronica',
                'description' => 'Alle elektronische apparaten en gadgets',
                'icon' => 'electric-plug',
                'sort_order' => 1,
                'active' => true
            ],
            [
                'name' => 'Gereedschap',
                'slug' => 'gereedschap', 
                'description' => 'Handgereedschap en elektrisch gereedschap',
                'icon' => 'wrench',
                'sort_order' => 2,
                'active' => true
            ],
            [
                'name' => 'Huishouden',
                'slug' => 'huishouden',
                'description' => 'Huishoudelijke apparaten en benodigdheden',
                'icon' => 'home',
                'sort_order' => 3,
                'active' => true
            ],
            [
                'name' => 'Sport',
                'slug' => 'sport',
                'description' => 'Sportartikelen en fitnessapparatuur',
                'icon' => 'dumbbell',
                'sort_order' => 4,
                'active' => true
            ],
            [
                'name' => 'Kleding',
                'slug' => 'kleding',
                'description' => 'Kledingstukken en accessoires',
                'icon' => 'shirt',
                'sort_order' => 5,
                'active' => true
            ],
            [
                'name' => 'Auto',
                'slug' => 'auto',
                'description' => 'Auto-onderdelen en accessoires',
                'icon' => 'car',
                'sort_order' => 6,
                'active' => true
            ],
            [
                'name' => 'Boeken',
                'slug' => 'boeken',
                'description' => 'Boeken, tijdschriften en educatief materiaal',
                'icon' => 'book',
                'sort_order' => 7,
                'active' => true
            ],
            [
                'name' => 'Overig',
                'slug' => 'overig',
                'description' => 'Alle andere items',
                'icon' => 'question-mark',
                'sort_order' => 99,
                'active' => true
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']], 
                $categoryData
            );
        }
    }
} 