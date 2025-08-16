<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $courseTypes = [
            'Introduction to',
            'Advanced',
            'Fundamentals of',
            'Principles of',
            'Essentials of',
            'Mastering',
            'Complete Guide to',
            'Professional',
            'Comprehensive',
            'Modern'
        ];

        $subjects = [
            'Computer Science',
            'Web Development',
            'Data Science',
            'Artificial Intelligence',
            'Machine Learning',
            'Software Engineering',
            'Database Management',
            'Cybersecurity',
            'Mobile Development',
            'Cloud Computing',
            'Network Administration',
            'Digital Marketing',
            'Business Analytics',
            'Project Management',
            'User Experience Design'
        ];

        $courseName = fake()->randomElement($courseTypes) . ' ' . fake()->randomElement($subjects);
        
        return [
            'name' => $courseName,
            'description' => fake()->paragraph(3, true) . ' ' . fake()->paragraph(2, true),
        ];
    }
}
