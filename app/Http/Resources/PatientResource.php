<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                           => $this->id,
            'nama'                         => $this->nama,
            'umur'                         => (int)($this->umur ?? 0),
            'jenis_kelamin'                => (string)($this->jenis_kelamin ?? ''),
            'riwayat_pelayanan_kesehatan'  => (string)($this->riwayat_pelayanan_kesehatan ?? ''),
            'riwayat_penyakit_jantung'     => (string)($this->riwayat_penyakit_jantung ?? 'Tidak'),
            'riwayat_merokok'              => (string)($this->riwayat_merokok ?? 'Tidak Pernah Merokok'),
            // Frontend kamu kadang pakai 'bmi' dan/atau 'indeks_bmi':
            'bmi'                          => $this->indeks_bmi !== null ? (float)$this->indeks_bmi : null,
            'indeks_bmi'                   => $this->indeks_bmi !== null ? (float)$this->indeks_bmi : null,
        ];
    }
}
