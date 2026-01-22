<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssistanceProgramsRequest extends FormRequest
{
    /**
     * Cek apakah user punya akses untuk request ini
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules validasi untuk form
     */
    public function rules(): array
    {
        $rules = [
            'nama_program'     => 'required|string|max:200',
            'tahun'            => 'required|integer|min:2000|max:2100',
            'periode'          => 'nullable|string|max:50',
            'target_penerima'  => 'required|in:KELUARGA,INDIVIDU',
            'desil_min'        => 'nullable|integer|min:1|max:10',
            'desil_max'        => 'nullable|integer|min:1|max:10|gte:desil_min',
            'status'           => 'required|in:PROSES,PENYALURAN,SELESAI',
            'tanggal_penyaluran' => 'nullable|date',
            'jam_mulai_pengambilan' => 'nullable|date_format:H:i',
            'jam_selesai_pengambilan' => 'nullable|date_format:H:i|after:jam_mulai_pengambilan',
            'keterangan'        => 'nullable|string',
        ];

        // Kalau update, wajib ada id
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }
}

