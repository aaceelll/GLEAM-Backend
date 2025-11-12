<?php

namespace App\Http\Controllers\Api\Manajemen;

use App\Http\Controllers\Controller;
use App\Models\QuizSubmission;
use Illuminate\Http\Request;

class QuizSubmissionController extends Controller
{
    // GET /api/manajemen/quiz/submissions
    // List semua submission (pre/post) dari semua user 
    public function allSubmissions(Request $request)
    {
        $tipe = $request->query('tipe'); // 'pre' atau 'post'

        $query = QuizSubmission::with(['user', 'bank'])
            ->orderBy('submitted_at', 'desc');

        if ($tipe && in_array($tipe, ['pre', 'post'])) {
            $query->where('tipe', $tipe);
        }

        $submissions = $query->get()->map(fn($s) => [
            'id' => $s->id,
            'user_id' => $s->user_id,
            'nama' => $s->user->nama ?? 'Tanpa Nama',
            'bank_name' => $s->bank->nama,
            'tipe' => $s->tipe,
            'total_score' => $s->total_score,
            'max_score' => $s->max_score,
            'percentage' => $s->percentage,
            'submitted_at' => $s->submitted_at->format('d M Y H:i'),
        ]);

        return response()->json(['data' => $submissions], 200);
    }

    // GET /api/manajemen/quiz/submissions/{id}
    // Detail submission + review jawaban  
    public function submissionDetail($id)
    {
        $submission = QuizSubmission::with(['bank.soal', 'user'])
            ->findOrFail($id);

        // Format review jawaban (sama seperti method detail)
        $review = [];
        foreach ($submission->bank->soal as $soal) {
            $soalId = (string)$soal->id;
            $userAnswer = $submission->answers[$soalId] ?? null;

            $selectedOption = null;
            $correctOption = null;

            if ($soal->tipe === 'pilihan_ganda' && $soal->opsi) {
                $selectedOption = collect($soal->opsi)->firstWhere('no', (int)$userAnswer);
                
                // Cari opsi dengan skor tertinggi (jawaban benar)
                $maxSkor = collect($soal->opsi)->max('skor');
                $correctOption = collect($soal->opsi)->firstWhere('skor', $maxSkor);
            }

            $review[] = [
                'soal_id' => $soal->id,
                'teks' => $soal->teks,
                'tipe' => $soal->tipe,
                'user_answer' => $userAnswer,
                'user_answer_text' => $selectedOption['teks'] ?? '-',
                'user_score' => $selectedOption['skor'] ?? 0,
                'correct_answer_text' => $correctOption['teks'] ?? '-',
                'correct_score' => $correctOption['skor'] ?? 0,
                'is_correct' => ($selectedOption['skor'] ?? 0) === ($correctOption['skor'] ?? 0),
            ];
        }

        return response()->json([
            'submission' => [
                'id' => $submission->id,
                'user_id' => $submission->user_id,
                'nama' => $submission->user->nama ?? 'Tanpa Nama',
                'bank_name' => $submission->bank->nama,
                'tipe' => $submission->tipe,
                'total_score' => $submission->total_score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->percentage,
                'submitted_at' => $submission->submitted_at->format('d M Y H:i'),
                'answers' => $submission->answers,
            ],
            'review' => $review,
        ], 200);
    }
}
