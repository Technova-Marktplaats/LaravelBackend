<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Category;

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
        $itemTitles = [
            'Vintage fiets', 'Boormachine', 'Koffiezetapparaat', 'Laptop', 
            'Winterjas', 'Voetbal', 'Boekenkast', 'Kinderwagen', 
            'Gitaar', 'Tuinslang', 'Stofzuiger', 'Magnetron', 
            'Sneakers', 'Tennisracket', 'Bureau', 'Speelgoedauto'
        ];

        // Gebruik een bestaande categorie of maak er een aan als er geen bestaan
        $categoryId = Category::active()->inRandomOrder()->first()?->id 
            ?? Category::factory()->create()->id;

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement($itemTitles),
            'description' => $this->faker->paragraph(3),
            'category_id' => $categoryId,
            'available' => true, // 80% kans dat het beschikbaar is
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

    /**
     * Create item with specific category
     */
    public function withCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
} 