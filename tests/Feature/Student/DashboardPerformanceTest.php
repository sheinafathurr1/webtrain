<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Track;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test: the dashboard's "in-progress courses" card used to
     * query modules+lessons+completed-ids separately for every distinct
     * course the student had touched (Course::progressPercentFor() /
     * nextLessonFor() each re-querying per course), so the query count grew
     * linearly with how many courses a student had started. Course rows are
     * now eager-loaded with modules.lessons up front and the user's full
     * completed-lesson-id set is computed once and passed in, so completing
     * lessons across more courses must not add more queries.
     */
    public function test_dashboard_query_count_does_not_scale_with_number_of_courses(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Warm Spatie's static permission cache before measuring: its first
        // load of the global permissions/roles list costs an extra query
        // that later requests in the same process skip, which would
        // otherwise make the second measurement look cheaper for reasons
        // unrelated to the dashboard itself.
        $warmupUser = User::factory()->create();
        $warmupUser->assignRole('Student');
        $this->actingAs($warmupUser)->get('/dashboard')->assertOk();

        $student = User::factory()->create();
        $student->assignRole('Student');

        $this->completeOneLessonPerCourse($student, courseCount: 2);
        $queriesWithTwoCourses = $this->countQueriesFor(fn () => $this->actingAs($student)->get('/dashboard'));

        $student2 = User::factory()->create();
        $student2->assignRole('Student');
        $this->completeOneLessonPerCourse($student2, courseCount: 6);
        $queriesWithSixCourses = $this->countQueriesFor(fn () => $this->actingAs($student2)->get('/dashboard'));

        $this->assertSame(
            $queriesWithTwoCourses,
            $queriesWithSixCourses,
            'Dashboard query count should not grow with the number of distinct courses a student has started.'
        );
    }

    private function completeOneLessonPerCourse(User $user, int $courseCount): void
    {
        $track = Track::factory()->create();

        for ($i = 0; $i < $courseCount; $i++) {
            $course = Course::factory()->create(['track_id' => $track->id]);
            $module = Module::factory()->create(['course_id' => $course->id]);
            $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true]);

            UserProgress::factory()->create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'completed_at' => now(),
            ]);
        }
    }

    private function countQueriesFor(\Closure $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $callback()->assertOk();

        DB::flushQueryLog();
        \Illuminate\Support\Facades\Event::forget('Illuminate\Database\Events\QueryExecuted');

        return $count;
    }
}
