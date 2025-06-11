<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Elektronica' => 'electric-plug',
            'Gereedschap' => 'wrench', 
            'Huishouden' => 'home',
            'Sport' => 'dumbbell',
            'Kleding' => 'shirt',
            'Auto' => 'car',
            'Boeken' => 'book',
            'Overig' => 'question-mark'
        ];

        $name = $this->faker->randomElement(array_keys($categories));
        $baseSlug = Str::slug($name);
        
        // Zorg voor unieke slug door een random suffix toe te voegen
        $slug = $baseSlug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return [
            'name' => $name . ($counter > 1 ? ' ' . $counter : ''),
            'slug' => $slug,
            'description' => $this->faker->sentence(),
            'icon' => $categories[$name],
            'active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10)
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
} 