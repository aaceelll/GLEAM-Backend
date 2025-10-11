<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Soal extends Model
{
    protected $table = 'questions';

    protected $fillable = [
        'bank_id', 'teks', 'tipe', 'bobot', 'kunci', 'opsi'
    ];

    protected $casts = [
        'opsi' => 'array',
    ];

    public function bank()
    {
        return $this->belongsTo(BankSoal::class, 'bank_id');
    }

    /**
     * Sinkron status + sentuh updated_at bank.
     * - ada ≥1 soal  => publish
     * - 0 soal       => draft
     */
    public static function syncBankVisibility(int $bankId): void
    {
        $count = static::where('bank_id', $bankId)->count();
        $next  = $count > 0 ? BankSoal::STATUS_PUBLISH : BankSoal::STATUS_DRAFT;

        // pakai query builder biar pasti update, dan sentuh updated_at
        DB::table('question_banks')
            ->where('id', $bankId)
            ->update([
                'status'     => $next,
                'updated_at' => now(),
            ]);
    }

    /**
     * Hook Eloquent sesudah commit:
     * tersambung ke semua jalur (controller, seeder, factory, dll).
     */
    protected static function booted()
    {
        // saat berhasil disimpan (create/update)
        static::saved(function (Soal $soal) {
            if ($soal->bank_id) {
                static::syncBankVisibility((int)$soal->bank_id);
            }
        });

        // saat dihapus
        static::deleted(function (Soal $soal) {
            if ($soal->bank_id) {
                static::syncBankVisibility((int)$soal->bank_id);
            }
        });
    }
}
