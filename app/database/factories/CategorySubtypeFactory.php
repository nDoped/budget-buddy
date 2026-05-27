<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CategoryType;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategorySubtype>
 */
class CategorySubtypeFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'category_type_id' => CategoryType::factory(),
            'user_id' => User::factory(),
        ];
    }
}
