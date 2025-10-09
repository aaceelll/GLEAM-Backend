<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\QuizSubmission;
use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizSubmissionController extends Controller
{
    /**
     * POST /api/quiz/submit
     * Submit jawaban quiz & hitung skor
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:question_banks,id',
            'answers' => 'required|array', // Format: { "soal_id": opsi_no }
        ]);

        $user = Auth::user();
        $bankId = $validated['bank_id'];
        $answers = $validated['answers'];

        // Ambil bank soal dengan tipe dari pivot
        $bank = BankSoal::with(['soal'])->findOrFail($bankId);
        
        // Ambil tipe (pre/post) dari tabel pivot materi_bank_soal
        // Ambil tipe (pre/post) dari tabel pivot
$tipeFromPivot = DB::table('materi_bank_soal')
    ->where('bank_id', $bankId)
    ->value('tipe');

// Fallback: detect dari nama bank soal
if (!$tipeFromPivot) {
    $bankName = strtolower($bank->nama);
    $tipeFromPivot = str_contains($bankName, 'post') ? 'post' : 'pre';
}

$tipe = $tipeFromPivot;

        // Hitung skor
        $totalScore = 0;
        $maxScore = 0;

        foreach ($bank->soal as $soal) {
            $soalId = (string)$soal->id;
            $userAnswer = $answers[$soalId] ?? null;

            if ($soal->tipe === 'pilihan_ganda' && $userAnswer && $soal->opsi) {
                // Cari skor dari opsi yang dipilih
                $selectedOption = collect($soal->opsi)->firstWhere('no', (int)$userAnswer);
                
                if ($selectedOption) {
                    $totalScore += $selectedOption['skor'] ?? 0;
                }

                // Hitung skor maksimal
                $maxScoreOption = collect($soal->opsi)->max('skor');
                $maxScore += $maxScoreOption ?? 0;
            }
        }

        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;

        // Simpan ke database
        $submission = QuizSubmission::create([
            'user_id' => $user->id,
            'bank_id' => $bankId,
            'tipe' => $tipe,
            'total_score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'answers' => $answers,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Quiz berhasil diselesaikan!',
            'data' => [
                'submission_id' => $submission->id,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'tipe' => $tipe,
            ]
        ]);
    }

    /**
     * GET /api/quiz/history
     * Tampilkan riwayat quiz user (group by tipe: pre/post)
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        $submissions = QuizSubmission::with(['bank'])
            ->where('user_id', $user->id)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->groupBy('tipe');

        return response()->json([
            'pre' => $submissions->get('pre', collect())->map(fn($s) => [
                'id' => $s->id,
                'bank_name' => $s->bank->nama,
                'total_score' => $s->total_score,
                'max_score' => $s->max_score,
                'percentage' => $s->percentage,
                'submitted_at' => $s->submitted_at->format('d M Y H:i'),
            ]),
            'post' => $submissions->get('post', collect())->map(fn($s) => [
                'id' => $s->id,
                'bank_name' => $s->bank->nama,
                'total_score' => $s->total_score,
                'max_score' => $s->max_score,
                'percentage' => $s->percentage,
                'submitted_at' => $s->submitted_at->format('d M Y H:i'),
            ]),
        ]);
    }

    /**
     * GET /api/quiz/history/{id}
     * Detail hasil quiz + review jawaban
     */
    public function detail($id)
    {
        $user = Auth::user();

        $submission = QuizSubmission::with(['bank.soal'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Format review jawaban
        $review = [];
        foreach ($submission->bank->soal as $soal) {
            $soalId = (string)$soal->id;
            $userAnswer = $submission->answers[$soalId] ?? null;

            $selectedOption = null;
            $correctOption = null;
            $scoredOption = null;

            if ($soal->tipe === 'pilihan_ganda' && $soal->opsi) {
                $selectedOption = collect($soal->opsi)->firstWhere('no', (int)$userAnswer);
                
                // Cari opsi dengan skor tertinggi (jawaban benar)
                $maxSkor = collect($soal->opsi)->max('skor');
                $correctOption = collect($soal->opsi)->firstWhere('skor', $maxSkor);
                
                $scoredOption = $selectedOption;
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
                'bank_name' => $submission->bank->nama,
                'tipe' => $submission->tipe,
                'total_score' => $submission->total_score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->percentage,
                'submitted_at' => $submission->submitted_at->format('d M Y H:i'),
            ],
            'review' => $review,
        ]);
    }
}