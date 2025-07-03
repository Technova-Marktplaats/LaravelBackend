<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Item;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItemImage>
 */
class ItemImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Realistische placeholder images voor verschillende categorieën
        $imageUrls = [
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 10000),
        ];

        return [
            'item_id' => Item::factory(),
            'url' => $this->faker->randomElement($imageUrls),
        ];
    }
} 