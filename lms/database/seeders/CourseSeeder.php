<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Professor;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $professors = Professor::all();
        
        Course::factory(10)->create()->each(function ($course, $index) use ($professors) {
            if ($index < $professors->count()) {
                $course->update(['professor_id' => $professors[$index]->id]);
            }
        });
    }
}
