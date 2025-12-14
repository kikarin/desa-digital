<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistanceRecipientsRequest extends FormRequest
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
            'assistance_program_id' => 'required|exists:assistance_programs,id',
            'target_type'           => 'required|in:KELUARGA,INDIVIDU',
        ];

        // Validasi berdasarkan target_type
        if ($this->target_type === 'KELUARGA') {
            $rules['family_id'] = [
                'required',
                'exists:families,id',
                Rule::unique('assistance_recipients', 'family_id')
                    ->where('assistance_program_id', $this->assistance_program_id)
                    ->where('target_type', 'KELUARGA')
                    ->whereNull('deleted_at')
                    ->ignore($this->id),
            ];
            $rules['resident_id'] = 'nullable';
        } else {
            $rules['resident_id'] = [
                'required',
                'exists:residents,id',
                Rule::unique('assistance_recipients', 'resident_id')
                    ->where('assistance_program_id', $this->assistance_program_id)
                    ->where('target_type', 'INDIVIDU')
                    ->whereNull('deleted_at')
                    ->ignore($this->id),
            ];
            $rules['family_id'] = 'nullable';
        }

        // Kalau update, wajib ada id
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        // Field opsional
        $rules['kepala_keluarga_id'] = 'nullable|exists:residents,id';
        $rules['penerima_lapangan_id'] = 'nullable|exists:residents,id';
        $rules['status'] = 'nullable|in:PROSES,DATANG,TIDAK_DATANG';
        $rules['tanggal_penyaluran'] = 'nullable|date';
        $rules['catatan'] = 'nullable|string';

        return $rules;
    }

    /**
     * Custom messages untuk validasi
     */
    public function messages(): array
    {
        return [
            'family_id.unique'   => 'Keluarga ini sudah terdaftar sebagai penerima di program ini.',
            'resident_id.unique' => 'Warga ini sudah terdaftar sebagai penerima di program ini.',
        ];
    }
}

