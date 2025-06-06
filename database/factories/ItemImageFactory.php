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
            'https://picsum.photos/400/300?random=' . $this->faker->numberBetween(1, 1000),
            'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&h=300&fit=crop',
            'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400&h=300&fit=crop',
            'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=300&fit=crop',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        ];

        return [
            'item_id' => Item::factory(),
            'url' => $this->faker->randomElement($imageUrls),
        ];
    }
} 