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

    // Konversi otomatis untuk kolom JSON
    protected $casts = [
        'opsi' => 'array',
    ];

    // Relasi: setiap soal dimiliki oleh satu bank soal
    public function bank()
    {
        return $this->belongsTo(BankSoal::class, 'bank_id');
    }

    // update status bank soal berdasarkan jumlah soal yang ada
    public static function syncBankVisibility(int $bankId): void
    {
        // Hitung jumlah soal dalam bank soal tertentu
        $count = static::where('bank_id', $bankId)->count();
        // Jika ada minimal satu soal → publish, jika kosong → draft
        $next  = $count > 0 ? BankSoal::STATUS_PUBLISH : BankSoal::STATUS_DRAFT;

        // Perbarui status di tabel induk question_banks
        DB::table('question_banks')
            ->where('id', $bankId)
            ->update([
                'status'     => $next,
                'updated_at' => now(),
            ]);
    }

    // Hook otomatis setelah operasi database berhasil
    protected static function booted()
    {
        // Saat soal disimpan (create/update)
        static::saved(function (Soal $soal) {
            if ($soal->bank_id) {
                static::syncBankVisibility((int)$soal->bank_id);
            }
        });

        // saat soal dihapus
        static::deleted(function (Soal $soal) {
            if ($soal->bank_id) {
                static::syncBankVisibility((int)$soal->bank_id);
            }
        });
    }
}
