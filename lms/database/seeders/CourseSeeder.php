<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Professor;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $professors = Professor::all();
        
        Course::factory(10)->create()->each(function ($course, $index) use ($professors) {
            // Assign a professor to each course (one-to-one relationship)
            if ($index < $professors->count()) {
                $course->update(['professor_id' => $professors[$index]->id]);
            }
        });
    }
}
