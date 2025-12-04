<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BankSoal;
use App\Models\Materi;

class QuizController extends Controller
{
    /**
     * Default: semua bank publish yg punya soal (>0), tanpa filter materi.
     * (Tetap seperti sebelumnya)
     */
    public function banksDefault()
    {
        $banks = BankSoal::query()
            ->where('status', BankSoal::STATUS_PUBLISH)
            ->withCount('soal')
            ->having('soal_count', '>', 0)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (BankSoal $b) => [
                'id'        => $b->id,
                'nama'      => $b->nama,
                'tipe'      => $b->tipe ?? null, // kalau ada kolom/atribut ini
                'totalSoal' => (int) $b->soal_count,
                'status'    => $b->status,
                'updatedAt' => optional($b->updated_at)->toDateTimeString(),
                'source'    => 'banks',
                'bank_id'   => $b->id,
                'durasiMenit'=> null,
            ]);

        return response()->json(['data' => $banks]);
    }

    /**
     * NEW: /api/quiz/banks/by-materi?slug=diabetes-melitus
     *      /api/quiz/banks/by-materi?materi_id=123
     *
     * Hanya mengembalikan bank publish + punya ≥1 soal yang TERHUBUNG
     * ke materi melalui pivot materi_bank_soal (is_active = true).
     * Tidak mengganggu endpoint lain.
     */
    public function banksByMateri(Request $request)
    {
        $slug = $request->query('slug');
        $materiId = $request->query('materi_id');

        $materi = null;
        if ($slug) {
            $materi = Materi::where('slug', $slug)->first();
        } elseif ($materiId) {
            $materi = Materi::find($materiId);
        }

        // Kalau materi tidak ketemu, tetap kembalikan array kosong dengan struktur aman.
        if (!$materi) {
            return response()->json(['data' => []]);
        }

        // Filter bank publish + punya soal + terhubung di pivot aktif
        $banks = BankSoal::query()
            ->where('question_banks.status', BankSoal::STATUS_PUBLISH)
            ->join('materi_bank_soal as mbs', 'mbs.bank_id', '=', 'question_banks.id')
            ->where('mbs.materi_id', $materi->id)
            ->where('mbs.is_active', true)
            ->withCount('soal')
            ->having('soal_count', '>', 0)
            ->orderByDesc('question_banks.updated_at')
            ->get([
                'question_banks.*',
                'mbs.tipe as pivot_tipe',
                'mbs.urutan as pivot_urutan',
            ]);

        $data = $banks->map(function (BankSoal $b) {
            return [
                'id'          => $b->id,
                'nama'        => $b->nama,
                'deskripsi'   => null,
                'totalSoal'   => (int) $b->soal_count,
                'durasiMenit' => null,
                'bank_id'     => $b->id,
                'source'      => 'banks',
                'tipe'        => $b->pivot_tipe ?? null,  // pre/post dari pivot
                'updatedAt'   => optional($b->updated_at)->toDateTimeString(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * List soal public dari satu bank (tanpa kunci jawaban).
     */
    public function listSoalPublic(BankSoal $bank)
    {
        $items = $bank->soal()
            ->orderBy('id')
            ->get(['id','teks','tipe','opsi','bobot']);

        return response()->json(['data' => $items]);
    }

    private function baseName($name)
    {
        $clean = strtolower($name);
        $clean = str_replace(['pre-test', 'post-test', 'pre', 'post'], '', $clean);
        $clean = str_replace(['test', 'kuis', 'quiz', 'kuisioner'], '', $clean);

        // hanya huruf/angka, spasi tunggal
        return trim(preg_replace('/[^a-z0-9]+/', ' ', $clean));
    }

    public function getAvailableTests(Request $request)
    {
        $user = $request->user();

        // Ambil semua bank soal yang aktif & punya soal
        $banks = BankSoal::query()
            ->where('status', BankSoal::STATUS_PUBLISH)
            ->withCount('soal')
            ->having('soal_count', '>', 0)
            ->get();

        // Ambil submission user untuk cek apakah pre-test sudah dikerjakan
        $userSubs = \App\Models\QuizSubmission::where('user_id', $user->id)->get();
        $result = [];

        foreach ($banks as $bank) {
            // Cari tipe dari relasi pivot
            $tipe = DB::table('materi_bank_soal')
                ->where('bank_id', $bank->id)
                ->value('tipe');

            // Fallback kalau pivot kosong
            if (!$tipe) {
                $lower = strtolower($bank->nama);
                $tipe = str_contains($lower, 'post') ? 'post' : 'pre';
            }

            // Cek apakah user sudah mengerjakan test ini
            $isDone = $userSubs->where('bank_id', $bank->id)->isNotEmpty();

            // LOGIC LOCKING
            $isLocked = false;
            if ($tipe === 'post') {
            $postBase = $this->baseName($bank->nama);
            $preBank = $banks->first(function ($b) use ($postBase) {

                // Ambil tipe dari pivot
                $preTipe = DB::table('materi_bank_soal')
                    ->where('bank_id', $b->id)
                    ->value('tipe');

                // fallback dari nama
                if (!$preTipe) {
                    $lower = strtolower($b->nama);
                    $preTipe = str_contains($lower, 'post') ? 'post' : 'pre';
                }

                return $this->baseName($b->nama) === $postBase
                    && strtolower($preTipe) === 'pre';
            });

            if ($preBank && $userSubs->where('bank_id', $preBank->id)->isEmpty()) {
                $isLocked = true;
            }
        }

            $result[] = [
                'id'          => $bank->id,
                'nama'        => $bank->nama,
                'tipe'        => $tipe,          
                'isLocked'    => $isLocked,      
                'isDone'      => $isDone,
                'totalSoal'   => $bank->soal_count,
                'updatedAt'   => optional($bank->updated_at)->toDateTimeString(),
            ];
        }

        return response()->json(['data' => $result]);
    }
}
