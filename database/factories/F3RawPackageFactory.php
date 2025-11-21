<?php

namespace Database\Factories;

use App\Models\F3PackageName;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\F3RawPackage>
 */
class F3RawPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'raw_package' => $this->faker->word(),
            'lead_count'  => (string) $this->faker->numberBetween(1, 100),
            'package_id'  => F3PackageName::factory(), // creates a related package
            'dimension'   => $this->faker->regexify('[1-9]x[1-9]'),
        ];
    }
}
