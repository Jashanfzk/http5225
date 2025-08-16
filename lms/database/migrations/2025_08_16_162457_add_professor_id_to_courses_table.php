<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add professor_id to courses table
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('professor_id')->nullable()->constrained()->onDelete('set null');
        });

        // Drop the course_professor pivot table since we're changing to one-to-one
        Schema::dropIfExists('course_professor');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the course_professor pivot table
        Schema::create('course_professor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('professor_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Remove professor_id from courses table
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['professor_id']);
            $table->dropColumn('professor_id');
        });
    }
};
