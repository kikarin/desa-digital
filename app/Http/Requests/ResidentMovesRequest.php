<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResidentMovesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resident_id'    => 'required|exists:residents,id',
            'jenis_pindah'   => 'required|in:INDIVIDU,KELUARGA',
            'alamat_tujuan'  => 'required|string',
            'desa'           => 'required|string|max:100',
            'kecamatan'      => 'required|string|max:100',
            'kabupaten'      => 'required|string|max:100',
            'tanggal_pindah' => 'required|date',
        ];
    }
}

