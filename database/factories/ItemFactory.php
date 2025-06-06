<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Elektronica', 'Huishouden', 'Kleding', 'Sport & Fitness', 
            'Boeken', 'Meubels', 'Tuingereedschap', 'Speelgoed', 
            'Auto-onderdelen', 'Muziekinstrumenten'
        ];

        $itemTitles = [
            'Vintage fiets', 'Boormachine', 'Koffiezetapparaat', 'Laptop', 
            'Winterjas', 'Voetbal', 'Boekenkast', 'Kinderwagen', 
            'Gitaar', 'Tuinslang', 'Stofzuiger', 'Magnetron', 
            'Sneakers', 'Tennisracket', 'Bureau', 'Speelgoedauto'
        ];

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement($itemTitles),
            'description' => $this->faker->paragraph(3),
            'category' => $this->faker->randomElement($categories),
            'available' => $this->faker->boolean(80), // 80% kans dat het beschikbaar is
        ];
    }

    /**
     * Indicate that the item is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'available' => false,
        ]);
    }

    /**
     * Indicate that the item is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'available' => true,
        ]);
    }
} 