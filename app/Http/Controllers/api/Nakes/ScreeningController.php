<?php

namespace App\Http\Controllers\Api\Nakes;

use App\Http\Controllers\Controller;
use App\Models\DiabetesScreening;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    // GET /api/nakes/diabetes-screenings
    public function index(Request $r)
    {
        $q = DiabetesScreening::query()->with('user')->latest('created_at');

        if ($search = $r->query('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('patient_name', 'like', "%{$search}%");
            });
        }

        $rows = $q->get()->map(fn($x) => $this->toListRow($x))->values();
        return response()->json($rows);
    }

    // GET /api/nakes/diabetes-screenings/{id}
    public function show($id)
    {
        $x = DiabetesScreening::with('user')->findOrFail($id);
        return response()->json($this->toDetail($x));
    }

    // GET /api/nakes/users/{userId}/diabetes-screenings
    public function byUser($userId)
    {
        $rows = DiabetesScreening::where('user_id', $userId)
            ->latest('created_at')
            ->get()
            ->map(fn($x) => $this->toListRow($x))
            ->values();

        return response()->json($rows);
    }

    private function toListRow(DiabetesScreening $x): array
    {
        $pct = $this->parsePercent($x->diabetes_probability);

        return [
            'id'      => $x->id,
            'name'    => $x->patient_name ?: optional($x->user)->name,
            'date'    => $x->created_at ? $x->created_at->toIso8601String() : null,
            'riskPct' => $pct,
            'user_id' => $x->user_id,
        ];
    }

    private function toDetail(DiabetesScreening $x): array
    {
        $pct = $this->parsePercent($x->diabetes_probability);
        $label = is_numeric($pct) && $pct >= 60 ? 'Risiko Tinggi' : 'Risiko Rendah';

        return [
            'id'            => $x->id,
            'created_at'    => $x->created_at ? $x->created_at->toIso8601String() : null,
            'updated_at'    => $x->updated_at ? $x->updated_at->toIso8601String() : null,
            'riskPct'       => $pct,
            'riskLabel'     => $label,
            'nama'          => $x->patient_name ?: optional($x->user)->name,
            'usia'          => $x->age,
            'jenis_kelamin' => $x->gender,
            'bmi'           => $x->bmi,
            'sistolik'      => $x->systolic_bp,
            'diastolik'     => $x->diastolic_bp,
            'tekanan_darah' => ($x->systolic_bp && $x->diastolic_bp) ? "{$x->systolic_bp} / {$x->diastolic_bp}" : null,
            'riwayat_merokok' => $x->smoking_history,
            'riwayat_jantung' => $x->heart_disease,
            'gula_darah'       => $x->blood_glucose_level ? "{$x->blood_glucose_level} mg/dL" : null,
            'blood_sugar'      => $x->blood_glucose_level,
        ];
    }

    private function parsePercent($val): ?float
    {
        if ($val === null) return null;
        $num = is_string($val) ? str_replace('%', '', $val) : $val;
        $f = floatval($num);
        return is_finite($f) ? $f : null;
    }
}
