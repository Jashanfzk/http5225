<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Primary',
                'slug' => 'primary',
                'description' => 'Comprehensive resources for primary education',
                'icon' => 'bi bi-book',
            ],
            [
                'name' => 'Junior Intermediate',
                'slug' => 'junior-intermediate',
                'description' => 'Engaging content for junior intermediate students',
                'icon' => 'bi bi-mortarboard',
            ],
            [
                'name' => 'High School',
                'slug' => 'high-school',
                'description' => 'Advanced resources for high school education',
                'icon' => 'bi bi-graduation-cap',
            ],
        ];

        foreach ($divisions as $division) {
            Division::updateOrCreate(
                ['slug' => $division['slug']],
                $division
            );
        }
    }
}
