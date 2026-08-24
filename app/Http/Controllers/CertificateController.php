<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\UserProgress;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $user = $request->user();

        abort_unless($course->is_published, 404);
        abort_unless($course->progressPercentFor($user) === 100, 403, 'Selesaikan course ini terlebih dahulu untuk mendapatkan sertifikat.');

        $lessonIds = $course->publishedLessons()->pluck('id');

        $completedAt = UserProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->max('completed_at');

        $completedAt = $completedAt ? \Illuminate\Support\Carbon::parse($completedAt) : now();

        $certificate = Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['code' => Certificate::generateCode(), 'issued_at' => $completedAt]
        );

        $pdf = Pdf::loadView('certificates.course', [
            'user' => $user,
            'course' => $course,
            'completedAt' => $certificate->issued_at,
            'code' => $certificate->code,
            'verifyUrl' => route('certificates.verify', $certificate->code),
        ]);

        $filename = 'sertifikat-'.Str::slug($course->title).'.pdf';

        return $pdf->download($filename);
    }
}
